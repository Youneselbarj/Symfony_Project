<?php

namespace App\EventListener;

use App\Entity\LogEvent;
use App\Repository\LogEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestLoggerListener
{
    private EntityManagerInterface $em;
    private LogEventRepository $logRepo;

    private int $bruteForceThreshold = 5; // nb max de tentatives
    private int $bruteForceWindowMinutes = 5; // fenêtre en minutes

    public function __construct(EntityManagerInterface $em, LogEventRepository $logRepo)
    {
        $this->em = $em;
        $this->logRepo = $logRepo;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;

        $request = $event->getRequest();

        if (str_starts_with($request->getPathInfo(), '/_')) return;

        $ip = $request->getClientIp() ?? 'unknown';
        $route = $request->getPathInfo();
        $method = $request->getMethod();
        $userAgent = $request->headers->get('User-Agent');

        // ---------------------------
        // Initialisation du log
        // ---------------------------
        $eventType = 'page_visit';
        $level = 'info';
        $severity = 'LOW';
        $message = 'Page visitée';
        $attemptCount = null;
        $status = 'new';

        // ---------------------------
        // Gestion login
        // ---------------------------
        if ($route === '/login' && $method === 'POST') {
            $eventType = 'login_attempt';
            $message = 'Tentative de connexion';
            $level = 'info';
            $severity = 'MEDIUM';

            if ($request->query->get('error')) {
                $eventType = 'login_failed';
                $level = 'warning';
                $severity = 'MEDIUM';
                $message = 'Échec de connexion';

                // Vérifier les tentatives précédentes
                $window = new \DateTimeImmutable(sprintf('-%d minutes', $this->bruteForceWindowMinutes));

                $qb = $this->logRepo->createQueryBuilder('l');
                $qb->select('COUNT(l.id)')
                    ->andWhere('l.ip = :ip')
                    ->andWhere('l.eventType = :type')
                    ->andWhere('l.createdAt >= :since')
                    ->setParameter('ip', $ip)
                    ->setParameter('type', 'login_failed')
                    ->setParameter('since', $window);

                $attempts = (int) $qb->getQuery()->getSingleScalarResult();
                $attemptCount = $attempts + 1;

                // Déterminer le statut
                $status = $attemptCount > $this->bruteForceThreshold ? 'blocked' : 'new';

                // Brute force détecté → Critical
                if ($status === 'blocked') {
                    $level = 'critical';
                    $severity = 'CRITICAL';
                    $message = 'Brute force détecté';
                }
            }
        }

        // ---------------------------
        // Détection accès routes sensibles
        // ---------------------------
        $sensitiveRoutes = ['/admin', '/dashboard'];
        if (in_array($route, $sensitiveRoutes)) {
            $severity = max($this->severityValue($severity), $this->severityValue('HIGH'));
            $level = 'warning';
        }

        // ---------------------------
        // Création du log
        // ---------------------------
        $log = new LogEvent();
        $log->setLevel($level)
            ->setSeverity($severity)
            ->setMessage($message)
            ->setIp($ip)
            ->setRoute($route)
            ->setMethod($method)
            ->setEventType($eventType)
            ->setUserAgent($userAgent)
            ->setAttemptCount($attemptCount)
            ->setStatus($status);

        $this->em->persist($log);
        $this->em->flush();
    }

    /**
     * Convert severity string to numeric value
     * LOW=1, MEDIUM=2, HIGH=3, CRITICAL=4
     */
    private function severityValue(string $severity): int
    {
        return match($severity) {
            'LOW' => 1,
            'MEDIUM' => 2,
            'HIGH' => 3,
            'CRITICAL' => 4,
            default => 1,
        };
    }
}
