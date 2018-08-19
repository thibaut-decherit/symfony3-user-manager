<?php

namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class LoginFormAuthenticator
 *
 * See https://symfony.com/doc/current/security/guard_authentication.html#the-guard-authenticator-methods
 *
 * @package AppBundle\Security
 */
class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $em;
    private $router;
    private $passwordEncoder;
    private $csrfTokenManager;
    private $translatorInterface;

    /**
     * LoginFormAuthenticator constructor.
     * @param EntityManagerInterface $em
     * @param RouterInterface $router
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param TranslatorInterface $translatorInterface
     */
    public function __construct(
        EntityManagerInterface $em,
        RouterInterface $router,
        UserPasswordEncoderInterface $passwordEncoder,
        CsrfTokenManagerInterface $csrfTokenManager,
        TranslatorInterface $translatorInterface
    )
    {
        $this->em = $em;
        $this->router = $router;
        $this->passwordEncoder = $passwordEncoder;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->translatorInterface = $translatorInterface;
    }

    /**
     * @param Request $request
     * @return array|mixed|void
     */
    public function getCredentials(Request $request)
    {
        $loginCheckRoute = $this->router->generate('login');

        $isLoginSubmit = $request->getPathInfo() == $loginCheckRoute && $request->isMethod('POST');

        if (!$isLoginSubmit) {
            // skip authentication
            return;
        }

        $username = $request->request->get('_username');
        $password = $request->request->get('_password');
        $csrfToken = $request->request->get('_csrf_token');

        if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $csrfToken))) {
            throw new InvalidCsrfTokenException('Invalid CSRF token.');
        }

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $username
        );

        return [
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return \AppBundle\Entity\User|null|object|UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = $credentials['username'];

        return $this->em->getRepository('AppBundle:User')
            ->findOneBy(['email' => $username]);
    }

    /**
     * @param mixed $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $password = $credentials['password'];

        if ($this->passwordEncoder->isPasswordValid($user, $password)) {
            return true;
        }

        return false;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return JsonResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetPath = null;
        // if the user hits a secure page and start() was called, this was
        // the URL they were on, and probably where you want to redirect to
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);

        if (!$targetPath) {
            $targetPath = $this->router->generate('home');
        }

        return new JsonResponse(['url' => $targetPath], 200);
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($exception instanceof DisabledException) {
            $errorMessage = $this->translatorInterface->trans('user.account_disabled');
        } else {
            $errorMessage = $this->translatorInterface->trans('user.invalid_credentials');
        }

        return new JsonResponse([
            'errorMessage' => $errorMessage
        ], 400);
    }

    /**
     * @return string
     */
    protected function getLoginUrl()
    {
        return $this->router->generate('login');
    }

    /**
     * This is called if the client accesses a URI/resource that requires authentication, but no authentication details were sent.
     *
     * @param Request $request
     * @param AuthenticationException|null $exception
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $exception = null)
    {
        $url = $this->router->generate('home', ["authenticationRequiredRedirect" => true]);
        return new RedirectResponse($url);
    }

    /**
     * If you want to support "remember me" functionality, return true from this method
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        return true;
    }
}
