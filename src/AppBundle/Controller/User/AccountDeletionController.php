<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use AppBundle\Entity\User;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AccountDeletionController
 * @package AppBundle\Controller\User
 */
class AccountDeletionController extends DefaultController
{
    /**
     * Renders account deletion view.
     *
     * @return Response
     */
    public function showAction(): Response
    {
        return $this->render(':User:account-deletion.html.twig');
    }

    /**
     * Sends account deletion link by email to user then log him out.
     *
     * @Route("account/delete", name="account_deletion_request", methods="GET")
     * @return RedirectResponse
     * @throws Exception
     */
    public function requestAction(): RedirectResponse
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        // Generates account deletion token and retries if token already exists.
        $loop = true;
        while ($loop) {
            $token = $user->generateSecureToken();

            $duplicate = $em->getRepository('AppBundle:User')->findOneBy(['accountDeletionToken' => $token]);
            if (is_null($duplicate)) {
                $loop = false;
                $user->setAccountDeletionToken($token);
            }
        }

        $user->setAccountDeletionRequestedAt(new DateTime());

        $accountDeletionTokenLifetimeInMinutes = ceil($this->getParameter('account_deletion_token_lifetime') / 60);
        $this->get('mailer.service')->accountDeletionRequest($user, $accountDeletionTokenLifetimeInMinutes);

        $em->flush();

        /*
         * Confirmation flash alert is handled by AppBundle/Security/AccountDeletionLogoutHandler which will retrieve
         * the following session attribute to know it has to do so.
         */
        $this->get('session')->set('account-deletion-request', true);

        return $this->redirectToRoute('logout');
    }

    /**
     * Removes user matching deletion token if token is not expired.
     *
     * @param User|null $user (default to null so param converter doesn't throw 404 error if no user found)
     * @Route("/delete-account/{accountDeletionToken}", name="account_deletion", methods="GET")
     * @return RedirectResponse
     */
    public function deleteAction(User $user = null): RedirectResponse
    {
        if ($user === null) {
            $this->addFlash(
                'account-deletion-error',
                $this->get('translator')->trans('flash.user.account_deletion_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $currentUser = $this->getUser();

        /*
         * If user requesting deletion is logged in, he is logged out and account deletion is handled to
         * AppBundle/EventListener/AccountDeletionLogoutHandler to prevent 500 error "$user must be an instanceof
         * UserInterface, an object implementing a __toString method, or a primitive string."
         */
        if ($currentUser !== null && $currentUser === $user) {
            $this->get('session')->set('account-deletion-confirmation', $user->getAccountDeletionToken());

            return $this->redirectToRoute('logout');
        }

        $em = $this->getDoctrine()->getManager();

        $accountDeletionTokenLifetime = $this->getParameter('account_deletion_token_lifetime');

        if ($user->isAccountDeletionTokenExpired($accountDeletionTokenLifetime)) {
            $user->setAccountDeletionRequestedAt(null);
            $user->setAccountDeletionToken(null);

            $em->flush();

            $this->addFlash(
                'account-deletion-error',
                $this->get('translator')->trans('flash.user.account_deletion_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $em->remove($user);
        $em->flush();

        $this->get('mailer.service')->accountDeletionSuccess($user);

        $this->addFlash(
            'account-deletion-success',
            $this->get('translator')->trans('flash.user.account_deletion_success')
        );

        return $this->redirectToRoute('home');
    }
}
