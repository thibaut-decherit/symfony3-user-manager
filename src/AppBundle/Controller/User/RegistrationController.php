<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class RegistrationController
 * @package AppBundle\Controller\User
 */
class RegistrationController extends DefaultController
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

        return $this->render('User/registration.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Handles the registration form submitted with ajax.
     *
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @Route("/register-ajax", name="registration_ajax")
     * @Method("POST")
     * @return JsonResponse
     * @throws \Twig\Error\Error
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\User\RegistrationType', $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordEncoder->encodePassword($user, $user->getPassword());

            $em = $this->getDoctrine()->getManager();
            $user->setPassword($hashedPassword);
            $em->persist($user);
            $em->flush();

            $activationUrl = $this->generateUrl(
                'activate_account',
                [
                    'activationToken' => $user->getActivationToken()
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $this->container->get('mailer.service')->registrationSuccess($user, $activationUrl);

            // Renders and json encode the original form (needed to empty form fields)
            $user = new User();
            $form = $this->createForm('AppBundle\Form\User\RegistrationType', $user);
            $template = $this->render('Form/registration-form.html.twig', array(
                'form' => $form->createView(),
            ));
            $jsonTemplate = json_encode($template->getContent());

            return new JsonResponse([
                'template' => $jsonTemplate,
                'successMessage' => $this->get('translator')->trans('user.registration_success')
            ], 200);
        }

        // Renders and json encode the updated form (with errors and input values)
        $template = $this->render('User/registration-form.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
        $jsonTemplate = json_encode($template->getContent());

        // Returns the html form and 400 Bad Request status to js
        return new JsonResponse([
            'template' => $jsonTemplate
        ], 400);
    }
}
