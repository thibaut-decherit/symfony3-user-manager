<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use AppBundle\Entity\User;
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function activateAccountAction(User $user)
    {
        $em = $this->getDoctrine()->getManager();

        if ($user === null) {
            return $this->redirectToRoute('home');
        }

        if ($user->isActivated() === true) {
            $this->addFlash(
                "login-flash-success",
                $this->get('translator')->trans('flash.account_already_activated')
            );

            return $this->redirectToRoute('login');
        }

        $user->setActivated(true);

        $em->flush();

        $this->addFlash(
            "login-flash-success",
            $this->get('translator')->trans('flash.account_activated_successfully')
        );

        return $this->redirectToRoute('login');
    }
}
