<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use AppBundle\Entity\User;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class ManageAccountController
 * @package AppBundle\Controller\User
 *
 * @Route("/account")
 */
class ManageAccountController extends DefaultController
{
    /**
     * Renders user account view.
     *
     * @Route(name="account", methods="GET")
     * @return Response
     */
    public function manageAccountAction()
    {
        return $this->render(':User:manage-account.html.twig');
    }

    /**
     * Renders the account information edit form.
     *
     * @param UserInterface $user
     * @return Response
     */
    public function accountInformationFormAction(UserInterface $user)
    {
        $form = $this->createForm('AppBundle\Form\User\UserInformationType', $user);

        return $this->render(':Form/User:user-information.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Handles the account information edit form submitted with ajax.
     *
     * @param Request $request
     * @param UserInterface $user
     * @Route("/user-information-edit-ajax", name="user_information_edit_ajax", methods="POST")
     * @return JsonResponse
     */
    public function accountInformationEditAction(Request $request, UserInterface $user)
    {
        $form = $this->createForm('AppBundle\Form\User\UserInformationType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->getDoctrine()->getManager()->flush();

            $template = $this->render(':Form/User:user-information.html.twig', array(
                'form' => $form->createView()
            ));
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate,
                'successMessage' => $this->get('translator')->trans('user.information_updated')
            ], 200);
        }

        /*
         * $user must be refreshed or invalid POST data will conflict with logged-in user and crash the session,
         * this line is not needed when editing with ajax any other entity than User
         */
        $this->getDoctrine()->getManager()->refresh($user);

        // Renders and json encode the updated form (with errors and input values)
        $template = $this->render(':Form/User:user-information.html.twig', array(
            'form' => $form->createView(),
        ));
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }

    /**
     * Renders the email address change form.
     *
     * @param UserInterface $user
     * @return Response
     */
    public function emailChangeFormAction(UserInterface $user)
    {
        $form = $this->createForm('AppBundle\Form\User\EmailChangeType', $user);

        return $this->render(':Form/User:email-change.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Handles the email address change form submitted with ajax.
     *
     * @param Request $request
     * @param UserInterface $user
     * @Route("/email-change-request-ajax", name="email_change_request_ajax", methods="POST")
     * @return JsonResponse
     * @throws Exception
     */
    public function emailChangeRequestAction(Request $request, UserInterface $user)
    {
        $form = $this->createForm('AppBundle\Form\User\EmailChangeType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailChangeRequestRetryDelay = $this->getParameter('email_change_request_send_email_again_delay');

            $template = $this->render(':Form/User:email-change.html.twig', array(
                'form' => $form->createView()
            ));
            $jsonTemplate = json_encode($template->getContent());

            if ($user->getEmailChangePending() === $user->getEmail()) {
                /*
                 * $user must be refreshed or invalid POST data will conflict with logged-in user and crash the session,
                 * this line is not needed when editing with ajax any other entity than User
                 */
                $this->getDoctrine()->getManager()->refresh($user);

                return new JsonResponse([
                    'template' => $jsonTemplate,
                    'errorMessage' => $this->get('translator')->trans('user.already_current_email_address')
                ], 400);
            }

            // IF retry delay is not expired, only show success message without sending email and writing in database.
            if ($user->getEmailChangeRequestedAt() !== null
                && $user->isEmailChangeRequestRetryDelayExpired($emailChangeRequestRetryDelay) === false) {
                $this->getDoctrine()->getManager()->refresh($user);

                return new JsonResponse([
                    'template' => $jsonTemplate,
                    'successMessage' => $this->get('translator')->trans(
                        'user.email_address_change_request_sent', ['email_address' => $user->getEmailChangePending()]
                    )
                ], 200);
            }

            $em = $this->getDoctrine()->getManager();

            // Generates email change token and retries if token already exists.
            $loop = true;
            while ($loop) {
                $token = $user->generateSecureToken();

                $duplicate = $em->getRepository('AppBundle:User')->findOneBy(['emailChangeToken' => $token]);

                if (empty($duplicate)) {
                    $loop = false;
                    $user->setEmailChangeToken($token);
                }
            }

            $user->setEmailChangeRequestedAt(new DateTime());

            $emailChangeTokenLifetimeInMinutes = ceil($this->getParameter('email_change_token_lifetime') / 60);
            $this->get('mailer.service')->emailChange($user, $emailChangeTokenLifetimeInMinutes);

            $em->flush();

            $template = $this->render(':Form/User:email-change.html.twig', array(
                'form' => $form->createView()
            ));
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate,
                'successMessage' => $this->get('translator')->trans(
                    'user.email_address_change_request_sent', ['email_address' => $user->getEmailChangePending()]
                )
            ], 200);
        }

        $this->getDoctrine()->getManager()->refresh($user);

        // Renders and json encode the updated form (with errors)
        $template = $this->render(':Form/User:email-change.html.twig', array(
            'form' => $form->createView(),
        ));
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }

    /**
     * Handles the email address change when user clicks on verification link sent by email.
     *
     * @param User|null $user (default to null so param converter doesn't throw 404 error if no user found)
     * @Route("/email-change/{emailChangeToken}", name="email_change", methods="GET")
     * @return Response
     * @throws Exception
     */
    public function emailChangeAction(User $user = null)
    {
        if ($user === null) {
            $this->addFlash(
                "error",
                $this->get('translator')->trans('flash.email_change_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();
        $emailChangeTokenLifetime = $this->getParameter('email_change_token_lifetime');

        if ($user->isEmailChangeTokenExpired($emailChangeTokenLifetime)) {
            $user->setEmailChangePending(null);
            $user->setEmailChangeRequestedAt(null);
            $user->setEmailChangeToken(null);

            $em->flush();

            $this->addFlash(
                "error",
                $this->get('translator')->trans('flash.email_change_token_expired')
            );

            return $this->redirectToRoute('home');
        }

        $user->setEmailChangeToken(null);
        $user->setEmailChangeRequestedAt(null);
        $user->setEmail($user->getEmailChangePending());
        $user->setEmailChangePending(null);
        $em->flush();

        $this->addFlash(
            "success",
            $this->get('translator')->trans('flash.email_change_success', ['email_address' => $user->getEmail()])
        );

        return $this->redirectToRoute('home');
    }

    /**
     * Renders the password change form.
     *
     * @param UserInterface $user
     * @return Response
     */
    public function passwordChangeFormAction(UserInterface $user)
    {
        $form = $this->createForm('AppBundle\Form\User\PasswordChangeType', $user);

        return $this->render(':Form/User:password-change.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Handles the password change form submitted with ajax.
     *
     * @param Request $request
     * @param UserInterface $user
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @Route("/password-change-ajax", name="password_change_ajax", methods="POST")
     * @return JsonResponse
     */
    public function passwordChangeAction(Request $request, UserInterface $user, UserPasswordEncoderInterface $passwordEncoder)
    {
        $form = $this->createForm('AppBundle\Form\User\PasswordChangeType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPlainPassword());

            $user->setPassword($hashedPassword);
            $this->getDoctrine()->getManager()->flush();

            $template = $this->render(':Form/User:password-change.html.twig', array(
                'form' => $form->createView()
            ));
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate,
                'successMessage' => $this->get('translator')->trans('user.password_updated')
            ], 200);
        }

        /*
         * $user must be refreshed or invalid POST data will conflict with logged-in user and crash the session,
         * this line is not needed when editing with ajax any other entity than User
         */
        $this->getDoctrine()->getManager()->refresh($user);

        // Renders and json encode the updated form (with errors)
        $template = $this->render(':Form/User:password-change.html.twig', array(
            'form' => $form->createView(),
        ));
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }
}
