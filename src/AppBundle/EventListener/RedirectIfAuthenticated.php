<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class RedirectIfAuthenticated
 * @package AppBundle\EventListener
 */
class RedirectIfAuthenticated
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    /**
     * @var RequestStack
     */
    private $request;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * RedirectIfAuthenticated constructor
     * @param Security $security
     * @param AuthorizationCheckerInterface $authChecker
     * @param RequestStack $request
     * @param RouterInterface $router
     */
    public function __construct(
        Security $security,
        AuthorizationCheckerInterface $authChecker,
        RequestStack $request,
        RouterInterface $router
    )
    {
        $this->security = $security;
        $this->authChecker = $authChecker;
        $this->request = $request;
        $this->router = $router;
    }

    /**
     * Redirects to home if authenticated user attempts to access user management features that should only be available
     * to unauthenticated users (e.g. resetting password, registration, login...)
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        /*
         * $this->security->getToken() !== null is needed to prevent "AuthenticationCredentialsNotFoundException
         * (The token storage contains no authentication token. One possible reason may be that there is no firewall
         * configured for this URL.)" when user attempts to access an unknown route, and to prevent breaking the
         * profiler.
         *
         * Reason : The kernel is probably requested multiple times when user requests a route, and during some of
         * those kernel requests $this->security->getToken() doesn't yet return a token and the code contained in
         * this condition is executed too early in the "chain" of kernel requests, thus causing the error.
         */
        if ($this->security->getToken() !== null
            && $this->authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $forbiddenRoutes = [
                "login",
                "password_reset",
                "password_reset_request",
                "registration",
            ];

            $route = $this->request->getCurrentRequest()->get('_route');

            // If requested route is one of the forbidden routes
            if (in_array($route, $forbiddenRoutes)) {
                $referer = $this->request->getCurrentRequest()->headers->get('referer');
                $baseWebsiteUrl = $this->request->getCurrentRequest()->getSchemeAndHttpHost();

                // Base website url is removed from referer url so router can match result to existing route.
                $lastUrl = substr($referer, strpos($referer, $baseWebsiteUrl) + strlen($baseWebsiteUrl));

                /*
                 * Tries to redirect to route matching $lastUrl. If no match is found (most likely because $referer url
                 * comes from another website), it will throw ResourceNotFoundException.
                 * If no match is found, it redirects to home.
                 */
                try {
                    $redirectRoute = $this->router->getMatcher()->match($lastUrl)['_route'];

                    // If referer url matches one of the forbidden routes, redirect to home to prevent redirect loop.
                    if (in_array($redirectRoute, $forbiddenRoutes)) {
                        $url = $this->router->generate('home');
                    } else {
                        $url = $this->router->generate($redirectRoute);
                    }

                } catch (ResourceNotFoundException $resourceNotFoundException) {
                    $url = $this->router->generate('home');
                }

                $event->setResponse(new RedirectResponse($url));
            }
        }
    }
}
