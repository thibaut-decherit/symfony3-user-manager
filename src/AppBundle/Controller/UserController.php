<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends DefaultController
{
    /**
     * Renders the initial registration form.
     *
     * @Route("/register", name="registration")
     * @Method("GET")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registerFormAction()
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\User\RegistrationType', $user);

        return $this->render('user/registration.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Handles the registration form submitted with ajax.
     *
     * @param Request $request
     * @Route("/register-ajax", name="registration_ajax")
     * @Method("POST")
     * @return JsonResponse
     */
    public function registerAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\User\RegistrationType', $user);
        $encoder = $this->get('security.encoder_factory')->getEncoder($user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $encoder->encodePassword($user->getPassword(), $user->getSalt());

            $em = $this->getDoctrine()->getManager();
            $user->setPassword($hashedPassword);
            $em->persist($user);
            $em->flush();

            $this->container->get('mailer.service')->registrationSuccess($user);

            // Renders and json encode the original form (needed to empty form fields)
            $user = new User();
            $form = $this->createForm('AppBundle\Form\User\RegistrationType', $user);
            $template = $this->render('user/registration-form.html.twig', array(
                'form' => $form->createView(),
            ));
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate,
                'success_message' => $this->get('translator')->trans('user.registration_success')
            ], 200);
        }

        // Renders and json encode the updated form (with errors and input values)
        $template = $this->render('user/registration-form.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }

    /**
     * Handles the login.
     *
     * @Route("/login", name="login")
     * @Method({"GET", "POST"})
     */
    public function loginAction(AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $errorMessage = null;

        if ($error instanceof DisabledException) {
            $errorMessage = $this->get('translator')->trans('user.account_disabled');
        } elseif ($error) {
            $errorMessage = $this->get('translator')->trans('user.invalid_credentials');
        }

        return $this->render('user/login.html.twig', array(
            'errorMessage' => $errorMessage,
        ));
    }
}
