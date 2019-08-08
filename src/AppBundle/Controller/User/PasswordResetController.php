<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use AppBundle\Entity\User;
use DateTime;
use Exception;
use SensioLabs\Security\Exception\HttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class PasswordResettingController
 * @package AppBundle\Controller\User
 * @Route("password-reset")
 */
class PasswordResetController extends DefaultController
{
    /**
     * Renders and handles password resetting request form.
     *
     * @param Request $request
     * @Route(name="password_reset_request", methods={"GET", "POST"})
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function requestAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            if ($this->isCsrfTokenValid('password_reset_request', $request->get('csrfToken')) === false) {
                throw new HttpException(400);
            }

            $em = $this->getDoctrine()->getManager();
            $usernameOrEmail = $request->request->get('usernameOrEmail');

            if (preg_match('/^.+\@\S+\.\S+$/', $usernameOrEmail)) {
                $user = $em->getRepository('AppBundle:User')->findOneBy(['email' => $usernameOrEmail]);
            } else {
                $user = $em->getRepository('AppBundle:User')->findOneBy(['username' => $usernameOrEmail]);
            }

            $this->addFlash(
                "success",
                $this->get('translator')->trans('flash.password_reset_email_sent')
            );

            if ($user === null) {
                return $this->render(':User:password-reset-request.html.twig');
            }

            $passwordResettingRequestRetryDelay = $this->getParameter('password_reset_request_send_email_again_delay');

            if ($user->getPasswordResetRequestedAt() !== null
                && $user->isPasswordResetRequestRetryDelayExpired($passwordResettingRequestRetryDelay) === false) {
                return $this->render(':User:password-reset-request.html.twig');
            }

            // Generates password reset token and retries if token already exists.
            $loop = true;
            while ($loop) {
                $token = $user->generateSecureToken();

                $duplicate = $em->getRepository('AppBundle:User')->findOneBy(['passwordResetToken' => $token]);

                if (empty($duplicate)) {
                    $loop = false;
                    $user->setPasswordResetToken($token);
                }
            }

            $user->setPasswordResetRequestedAt(new DateTime());

            /*
             * Parameter (referenceType) UrlGeneratorInterface::ABSOLUTE_URL is needed to generate an url
             * containing the website's root url. Otherwise generated url will be broken.
             */
            $passwordResetUrl = $this->generateUrl(
                'password_reset',
                [
                    'passwordResetToken' => $user->getPasswordResetToken()
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $passwordResetTokenLifetime = $this->getParameter('password_reset_token_lifetime');
            $this->get('mailer.service')->passwordReset($user, $passwordResetUrl, $passwordResetTokenLifetime);

            $em->flush();
        }

        return $this->render(':User:password-reset-request.html.twig');
    }

    /**
     * Renders and handles password reset form.
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param User|null $user (default null so param converter doesn't throw 404 if no user found)
     * @Route("/reset/{passwordResetToken}", name="password_reset", methods={"GET", "POST"})
     * @return RedirectResponse|Response
     */
    public function resetAction(Request $request, UserPasswordEncoderInterface $passwordEncoder, User $user = null)
    {
        $em = $this->getDoctrine()->getManager();
        $passwordResetTokenLifetime = $this->getParameter('password_reset_token_lifetime');

        if ($user === null) {
            $this->addFlash(
                "error",
                $this->get('translator')->trans('flash.password_reset_token_expired')
            );

            return $this->redirectToRoute('password_reset_request');
        }

        if ($user->isPasswordResetTokenExpired($passwordResetTokenLifetime) === true) {
            $user->setPasswordResetRequestedAt(null);
            $user->setPasswordResetToken(null);

            $em->flush();

            $this->addFlash(
                "error",
                $this->get('translator')->trans('flash.password_reset_token_expired')
            );

            return $this->redirectToRoute('password_reset_request');
        }

        $form = $this->createForm('AppBundle\Form\User\PasswordResetType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPlainPassword());

            $user->setPassword($hashedPassword);
            $user->setPasswordResetRequestedAt(null);
            $user->setPasswordResetToken(null);

            /*
             * User just clicked a password reset link sent by email, so we consider the email address has successfully
             * been verified, even if user never actually clicked on the dedicated link sent in the activation email.
             */
            if ($user->isActivated() === false) {
                $user->setActivated(true);
            }

            $em->flush();

            $this->addFlash(
                "login-flash-success",
                $this->get('translator')->trans('flash.password_reset_success')
            );

            return $this->redirectToRoute('login');
        }

        // Password blacklist to be used by zxcvbn.
        $passwordBlacklist = [
            $user->getUsername(),
            $user->getEmail(),
            $user->getPasswordResetToken(),
            $user->getActivationToken(),
        ];

        return $this->render(':User:password-reset-reset.html.twig', array(
            'form' => $form->createView(),
            'passwordBlacklist' => json_encode($passwordBlacklist),
        ));
    }
}
