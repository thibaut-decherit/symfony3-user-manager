<?php

namespace AppBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HomeController
 * @package AppBundle\Controller
 */
class HomeController extends DefaultController
{
    /**
     * @Route("/", name="home")
     */
    public function homeAction()
    {
        return $this->render('home.html.twig');
    }
}