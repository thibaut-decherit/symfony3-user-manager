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
     * @param User|null $user (default to null so param converter doesn't throw 404 error if no user found)
     * @Route("/activate-account/{accountActivationToken}", name="account_activation", methods="GET")
     * @return RedirectResponse|Response
     */
    public function activateAction(User $user = null)
    {
        $this->addFlash(
            'account-activation-success',
            $this->get('translator')->trans('flash.user.account_activated_successfully')
        );

        if ($user !== null && $user->isActivated() === false) {
            $user->setActivated(true);
            $user->setAccountActivationToken(null);

            $this->getDoctrine()->getManager()->flush();
        }

        return $this->redirectToRoute('login');
    }
}
