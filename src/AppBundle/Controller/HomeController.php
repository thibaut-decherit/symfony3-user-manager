<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HomeController
 * @package AppBundle\Controller
 */
class HomeController extends DefaultController
{
    /**
     * Renders homepage.
     *
     * @Route(name="home")
     * @return Response
     */
    public function homeAction()
    {
        return $this->render('home.html.twig');
    }
}
