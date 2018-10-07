<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LoginController
 * @package AppBundle\Controller\User
 */
class LoginController extends DefaultController
{
    /**
     * Handles login.
     *
     * @Route("/login", name="login", methods={"GET", "POST"})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        return $this->render('User/login.html.twig');
    }
}
