<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET','POST'])]
    public function login(Request $request): Response
    {
        // Simuler un échec de login
        if ($request->isMethod('POST')) {
            return $this->redirect('/login?error=1');
        }

        return $this->render('login/index.html.twig');
    }
}
