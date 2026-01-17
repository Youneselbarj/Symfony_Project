<?php

namespace App\Repository;

use App\Entity\LogEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogEvent>
 */
class LogEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogEvent::class);
    }

    /**
     * Compte les échecs de connexion pour une IP donnée
     * dans une fenêtre de temps (brute force detection)
     */
    public function countFailedLogins(string $ip, \DateTime $since): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.ip = :ip')
            ->andWhere('l.eventType = :type')
            ->andWhere('l.createdAt >= :since')
            ->setParameter('ip', $ip)
            ->setParameter('type', 'login_failed')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les derniers événements critiques (dashboard)
     */
    public function findCriticalEvents(int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.level = :level')
            ->setParameter('level', 'critical')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche avancée (IP, mot-clé, type)
     */
    public function searchLogs(?string $ip, ?string $keyword, ?string $eventType): array
    {
        $qb = $this->createQueryBuilder('l');

        if ($ip) {
            $qb->andWhere('l.ip = :ip')
               ->setParameter('ip', $ip);
        }

        if ($keyword) {
            $qb->andWhere('l.message LIKE :kw')
               ->setParameter('kw', '%' . $keyword . '%');
        }

        if ($eventType) {
            $qb->andWhere('l.eventType = :type')
               ->setParameter('type', $eventType);
        }

        return $qb
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Suppression des logs anciens (rotation SIEM)
     */
    public function deleteOlderThan(\DateTime $date): int
    {
        return $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
