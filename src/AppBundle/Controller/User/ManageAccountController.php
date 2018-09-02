<?php

namespace AppBundle\Controller\User;

use AppBundle\Controller\DefaultController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/", name="account")
     * @Method({"GET"})
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function manageAccountAction()
    {
        return $this->render(':User:manage-account.html.twig');
    }
}
