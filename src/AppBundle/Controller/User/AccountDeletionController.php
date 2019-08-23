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
    public function showAction()
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
    public function requestAction()
    {
        /**
         * @var User
         */
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
         * User is logged out now to prevent flash message from being added to logged in session and never being
         * displayed because user is logged out by return $this->redirectToRoute('logout'); before the flash message
         * has a chance to show up.
         *
         * Warning: If a logout success handler or a logout handler interface is implemented
         * (see https://symfony.com/doc/current/security.html#logging-out) you will have to call it manually here as
         * it will not be triggered
         */
        $this->get('security.token_storage')->setToken(null);
        $this->get('session')->invalidate();

        /*
         * User is now logged-out so flash message will be added to anon session and properly displayed when anon
         * is redirected to home.
         */
        $successMessage = $this->render(':FlashAlert/Message/User:account-deletion-request-success.html.twig', [
            'user' => $user
        ]);
        $this->addFlash(
            'account-deletion-request-success-raw',
            $successMessage->getContent()
        );

        return $this->redirectToRoute('home');
    }

    /**
     * Removes user matching deletion token if token is not expired.
     *
     * @param User|null $user (default to null so param converter doesn't throw 404 error if no user found)
     * @Route("/delete-account/{accountDeletionToken}", name="account_deletion", methods="GET")
     * @return RedirectResponse
     */
    public function deleteAction(User $user = null)
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
         * If user as logged in again before clicking on deletion link, he is logged out to prevent 500 error
         * $user must be an instanceof UserInterface, an object implementing a __toString method, or a primitive string.
         *
         * This crash is maybe caused by a mismatch between session and database.
         *
         * Warning: If a logout success handler or a logout handler interface is implemented
         * (see https://symfony.com/doc/current/security.html#logging-out) you will have to call it manually here as
         * it will not be triggered
         */
        if ($currentUser !== null && $currentUser === $user) {
            $this->get('security.token_storage')->setToken(null);
            $this->get('session')->invalidate();
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
