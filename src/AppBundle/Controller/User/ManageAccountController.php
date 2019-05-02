<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
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
