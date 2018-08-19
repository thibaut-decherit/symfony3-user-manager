<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
     * @param string $accountToken
     * @Route("/activate-account/{accountToken}", name="activate_account")
     * @Method({"GET"})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function activateAccountAction(string $accountToken)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneBy(['accountToken' => $accountToken]);

        if ($user === null) {
            return $this->redirectToRoute('home');
        }

        $user->activateAccount();

        $em->persist($user);
        $em->flush();

        return $this->render('User/account-activation-success.html.twig');
    }
}
