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

// Form types for editing
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    // ---------------------- Dashboard Index ----------------------
    #[Route('/', name: 'dashboard_index', methods: ['GET'])]
    public function index(Request $request, LogEventRepository $logRepo, PaginatorInterface $paginator): Response
    {
        $query = $logRepo->createQueryBuilder('l');

        // -------- Filters --------
        if ($ip = $request->query->get('ip')) {
            $query->andWhere('l.ip = :ip')->setParameter('ip', $ip);
        }
        if ($eventType = $request->query->get('eventType')) {
            $query->andWhere('l.eventType = :eventType')->setParameter('eventType', $eventType);
        }
        if ($level = $request->query->get('level')) {
            $query->andWhere('l.level = :level')->setParameter('level', $level);
        }
        if ($status = $request->query->get('status')) {
            $query->andWhere('l.status = :status')->setParameter('status', $status);
        }
        if ($severity = $request->query->get('severity')) {
            $query->andWhere('l.severity = :severity')->setParameter('severity', $severity);
        }
        if ($fromDate = $request->query->get('from_date')) {
            $query->andWhere('l.createdAt >= :fromDate')->setParameter('fromDate', new \DateTimeImmutable($fromDate));
        }
        if ($toDate = $request->query->get('to_date')) {
            $query->andWhere('l.createdAt <= :toDate')->setParameter('toDate', new \DateTimeImmutable($toDate));
        }
        if ($search = $request->query->get('search')) {
            $query->andWhere('l.message LIKE :search OR l.route LIKE :search')->setParameter('search', '%'.$search.'%');
        }

        $query->orderBy('l.createdAt', 'DESC');

        // -------- Pagination --------
        $logs = $paginator->paginate($query, $request->query->getInt('page', 1), 20);

        return $this->render('dashboard/index.html.twig', ['logs' => $logs]);
    }

    // ---------------------- Delete single log ----------------------
    #[Route('/delete/{id}', name: 'dashboard_delete', methods: ['POST'])]
    public function delete(?LogEvent $log, EntityManagerInterface $em, Request $request): Response
    {
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

    // ---------------------- Delete all logs ----------------------
    #[Route('/delete/all', name: 'dashboard_delete_all', methods: ['POST'])]
    public function deleteAll(EntityManagerInterface $em): Response
    {
        $em->createQuery('DELETE FROM App\Entity\LogEvent l')->execute();
        $this->addFlash('success', 'All logs deleted!');
        return $this->redirectToRoute('dashboard_index');
    }

    // ---------------------- Delete older logs ----------------------
    #[Route('/delete/older', name: 'dashboard_delete_older', methods: ['POST'])]
    public function deleteOlder(Request $request, EntityManagerInterface $em): Response
    {
        $type = $request->request->get('type'); // 10days / 5hours
        $date = match($type) {
            '10days' => new \DateTimeImmutable('-10 days'),
            '5hours' => new \DateTimeImmutable('-5 hours'),
            default => null
        };

        if ($date) {
            $em->createQuery('DELETE FROM App\Entity\LogEvent l WHERE l.createdAt <= :date')
               ->setParameter('date', $date)
               ->execute();
            $this->addFlash('success', "Logs older than $type deleted!");
        }

        return $this->redirectToRoute('dashboard_index');
    }

    // ---------------------- View Log ----------------------
    #[Route('/view/{id}', name: 'dashboard_view', methods: ['GET'])]
    public function view(LogEvent $log): Response
    {
        return $this->render('dashboard/view.html.twig', [
            'log' => $log,
        ]);
    }

    // ---------------------- Edit Log ----------------------
    #[Route('/edit/{id}', name: 'dashboard_edit', methods: ['GET','POST'])]
    public function edit(Request $request, LogEvent $log, EntityManagerInterface $em): Response
    {
    if (!$log) {
        $this->addFlash('danger', 'Log not found!');
        return $this->redirectToRoute('dashboard_index');
    }

    // Build the form properly
    $form = $this->createFormBuilder($log)
        ->add('level', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Level',
        ])
        ->add('severity', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'choices' => [
                'LOW' => 'LOW',
                'MEDIUM' => 'MEDIUM',
                'HIGH' => 'HIGH',
                'CRITICAL' => 'CRITICAL',
            ],
            'label' => 'Severity',
        ])
        ->add('status', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'choices' => [
                'New' => 'new',
                'Blocked' => 'blocked',
                'Analysed' => 'analysed',
            ],
            'label' => 'Status',
        ])
        ->add('message', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
            'label' => 'Message',
        ])
        ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
            'label' => 'Update Log',
            'attr' => ['class' => 'btn btn-primary mt-3']
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($log);
        $em->flush();

        $this->addFlash('success', 'Log updated successfully!');
        return $this->redirectToRoute('dashboard_index');
    }

    return $this->render('dashboard/edit.html.twig', [
        'log' => $log,
        'form' => $form->createView(), // ✅ important
    ]);
    }
}
