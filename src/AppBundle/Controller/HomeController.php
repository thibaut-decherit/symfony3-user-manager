<?php

namespace AppBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

class HomeController extends DefaultController
{
    /**
     * @Route("/", name="home")
     */
    public function indexAction()
    {
        return $this->render('home.html.twig');
    }
}