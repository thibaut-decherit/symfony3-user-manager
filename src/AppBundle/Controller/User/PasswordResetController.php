<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use SensioLabs\Security\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
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
     *
     * @Route("/", name="password_reset_request")
     * @Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Twig\Error\Error
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

            if ($user === null) {
                $this->addFlash(
                    "error",
                    $this->get('translator')->trans('flash.user_not_found')
                );

                return $this->redirectToRoute('password_reset_request');
            }

            if ($user->getHasBeenActivated() === false) {
                $this->addFlash(
                    "error",
                    $this->get('translator')->trans('flash.account_not_yet_activated')
                );

                return $this->redirectToRoute('password_reset_request');
            }

            $passwordResettingRequestRetryDelay = $this->getParameter('password_reset_request_send_email_again_delay');

            if ($user->getPasswordResetRequestedAt() !== null
                && $user->isPasswordResetRequestRetryDelayExpired($passwordResettingRequestRetryDelay) === false) {
                // Displays a flash message informing user that he has to wait $limit minutes between each request
                $limit = ceil($passwordResettingRequestRetryDelay / 60);
                $this->addFlash(
                    "error",
                    $this->get('translator')->trans('flash.password_reset_request_retry_delay_non_expired', [
                        '%limit%' => $limit
                    ])
                );

                return $this->redirectToRoute('password_reset_request');
            }

            $user->setPasswordResetToken($user->generateSecureToken());
            $user->setPasswordResetRequestedAt(new \DateTime());

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

            $em->persist($user);
            $em->flush();

            $this->addFlash(
                "success",
                $this->get('translator')->trans('flash.password_reset_email_send')
            );
        }

        return $this->render('User/password-resetting-request.html.twig');
    }

    /**
     * Renders and handles password reset form.
     *
     * @param Request $request
     * @param User $user
     * @param UserPasswordEncoderInterface $passwordEncoder
     *
     * @Route("/reset/{passwordResetToken}", name="password_reset")
     * @Method({"GET", "POST"})
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function resetAction(Request $request, User $user, UserPasswordEncoderInterface $passwordEncoder)
    {
        $em = $this->getDoctrine()->getManager();
        $passwordResetTokenLifetime = $this->getParameter('password_reset_token_lifetime');

        if ($user !== null) {
            $this->addFlash(
                "error",
                $this->get('translator')->trans('flash.password_reset_token_expired')
            );

            return $this->redirectToRoute('password_reset_request');
        }

        if ($user->isPasswordResetTokenExpired($passwordResetTokenLifetime) === true) {
            $user->setPasswordResetRequestedAt(null);
            $user->setPasswordResetToken(null);

            $em->persist($user);
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
            $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPassword());

            $user->setPassword($hashedPassword);
            $user->setPasswordResetRequestedAt(null);
            $user->setPasswordResetToken(null);

            $em->flush();

            $this->addFlash(
                "success",
                $this->get('translator')->trans('flash.password_reset_success')
            );

            return $this->redirectToRoute('login');
        }

        return $this->render('User/password-resetting-reset.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
