<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends DefaultController
{
    /**
     * Handles the registration.
     *
     * @Route("/register", name="registration")
     * @Method({"GET", "POST"})
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
        }

        return $this->render('user/registration.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Handles the login.
     *
     * @Route("/login", name="login")
     * @Method({"GET", "POST"})
     */
    public function loginAction(AuthenticationUtils $authenticationUtils)
    {
        $loginError = false;

        if ($authenticationUtils->getLastAuthenticationError()) {
            $loginError = true;
        }

        return $this->render('user/login.html.twig', array(
            'loginError' => $loginError,
        ));
    }
}
