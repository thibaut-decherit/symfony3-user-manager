<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class LoginController
 * @package AppBundle\Controller\User
 */
class LoginController extends DefaultController
{
    /**
     * Handles the login.
     *
     * @param AuthenticationUtils $authenticationUtils
     * @Route("/login", name="login")
     * @Method({"GET", "POST"})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $errorMessage = null;

        if ($error instanceof DisabledException) {
            $errorMessage = $this->get('translator')->trans('user.account_not_activated');
        } elseif ($error) {
            $errorMessage = $this->get('translator')->trans('user.invalid_credentials');
        }

        return $this->render('User/login.html.twig', array(
            'errorMessage' => $errorMessage,
        ));
    }
}
