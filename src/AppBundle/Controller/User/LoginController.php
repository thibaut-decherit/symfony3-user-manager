<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LoginController
 * @package AppBundle\Controller\User
 */
class LoginController extends DefaultController
{
    /**
     * Handles the login.
     *
     * @Route("/login", name="login")
     * @Method("GET")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        return $this->render('User/login.html.twig');
    }
}
