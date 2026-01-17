<?php

namespace App\Controller;

use App\Entity\LogEvent;
use App\Repository\LogEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard')]
class LogEventController extends AbstractController
{
    // ---------------------- Dashboard principal ----------------------
    #[Route('/', name: 'dashboard_index', methods: ['GET'])]
    public function index(
        Request $request,
        LogEventRepository $logRepo,
        PaginatorInterface $paginator
    ): Response
    {
        $query = $logRepo->createQueryBuilder('l');

        // -------- Filtres --------
        $ip = $request->query->get('ip');
        if ($ip) {
            $query->andWhere('l.ip LIKE :ip')->setParameter('ip', '%'.$ip.'%');
        }

        $eventType = $request->query->get('eventType');
        if ($eventType) {
            $query->andWhere('l.eventType = :eventType')->setParameter('eventType', $eventType);
        }

        $level = $request->query->get('level');
        if ($level) {
            $query->andWhere('l.level = :level')->setParameter('level', $level);
        }

        $status = $request->query->get('status');
        if ($status) {
            $query->andWhere('l.status = :status')->setParameter('status', $status);
        }

        $search = $request->query->get('search');
        if ($search) {
            $query->andWhere('l.message LIKE :search OR l.route LIKE :search')
                  ->setParameter('search', '%'.$search.'%');
        }

        $query = $query->orderBy('l.createdAt', 'DESC');

        // -------- Pagination --------
        $logs = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('dashboard/index.html.twig', [
            'logs' => $logs,
        ]);
    }
    // ---------------------- View a single log ----------------------
#[Route('/view/{id}', name: 'dashboard_view', methods: ['GET'])]
public function view(LogEvent $log): Response
{
    return $this->render('dashboard/view.html.twig', [
        'log' => $log
    ]);
}

// ---------------------- Edit a single log ----------------------
#[Route('/edit/{id}', name: 'dashboard_edit', methods: ['GET','POST'])]
public function edit(Request $request, LogEvent $log, EntityManagerInterface $em): Response
{
    $form = $this->createForm(\App\Form\LogEventType::class, $log);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Log updated successfully!');
        return $this->redirectToRoute('dashboard_index');
    }

    return $this->render('dashboard/edit.html.twig', [
        'log' => $log,
        'form' => $form->createView()
    ]);
}

    // ---------------------- Supprimer tous les logs ----------------------
    #[Route('/delete/all', name: 'dashboard_delete_all', methods: ['POST'])]
    public function deleteAll(EntityManagerInterface $em): Response
    {
    $em->createQuery('DELETE FROM App\Entity\LogEvent')->execute();
    $em->clear();

    $this->addFlash('success', 'All logs deleted!');
    return $this->redirectToRoute('dashboard_index');
    }

    // ---------------------- Supprimer les logs anciens ----------------------
    #[Route('/delete/older', name: 'dashboard_delete_older', methods: ['POST'])]
    public function deleteOlder(Request $request, EntityManagerInterface $em): Response
    {
    $type = $request->request->get('type');

    $date = match ($type) {
        '10days' => new \DateTimeImmutable('-10 days'),
        '5hours' => new \DateTimeImmutable('-5 hours'),
        default => null,
    };

    if (!$date) {
        $this->addFlash('danger', 'Invalid delete range.');
        return $this->redirectToRoute('dashboard_index');
    }

    $em->createQuery(
        'DELETE FROM App\Entity\LogEvent l WHERE l.createdAt <= :date'
    )
    ->setParameter('date', $date)
    ->execute();

    $em->clear();

    $this->addFlash('success', 'Old logs deleted!');
    return $this->redirectToRoute('dashboard_index');
    }   
    // ---------------------- Supprimer un log ----------------------
    #[Route('/delete/{id}', name: 'dashboard_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, EntityManagerInterface $em, LogEventRepository $logRepo, int $id): Response
    {
        $log = $logRepo->find($id);

        if (!$log) {
            $this->addFlash('danger', 'Log not found!');
            return $this->redirectToRoute('dashboard_index');
        }

        if ($this->isCsrfTokenValid('delete'.$log->getId(), $request->request->get('_token'))) {
            $em->remove($log);
            $em->flush();
            $this->addFlash('success', 'Log deleted!');
        } else {
            $this->addFlash('danger', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('dashboard_index');
    }
}
