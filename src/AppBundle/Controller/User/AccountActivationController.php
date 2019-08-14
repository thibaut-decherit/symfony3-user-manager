<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AccountActivationController
 * @package AppBundle\Controller\User
 */
class AccountActivationController extends DefaultController
{
    /**
     * Handles account activation.
     *
     * @param User $user
     * @Route("/activate-account/{activationToken}", name="activate_account", methods="GET")
     * @return RedirectResponse|Response
     */
    public function activateAccountAction(User $user)
    {
        $this->addFlash(
            "login-flash-success",
            $this->get('translator')->trans('flash.account_activated_successfully')
        );

        if ($user !== null && $user->isActivated() === false) {
            $user->setActivated(true);

            $this->getDoctrine()->getManager()->flush();
        }

        return $this->redirectToRoute('login');
    }
}
