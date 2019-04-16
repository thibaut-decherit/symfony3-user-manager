<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class CspHeaderBuilder
 * @package AppBundle\EventListener
 */
class CspHeaderBuilder
{
    /**
     * @var RequestStack
     */
    private $request;

    /**
     * @var bool
     */
    private $strictPolicy;

    /**
     * Whitelist added to every directive
     * @var array
     */
    private $mainWhitelist;

    /**
     * Whitelist for connect-src directive
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/connect-src
     * @var array
     */
    private $connectWhitelist;

    /**
     * Whitelist for form-action directive
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/form-action
     * @var array
     */
    private $formActionWhitelist;

    /**
     * Whitelist for script-src directive
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/script-src
     * @var array
     */
    private $scriptWhitelist;

    /**
     * CspHeaderBuilder constructor.
     * @param RequestStack $request
     */
    public function __construct(RequestStack $request)
    {
        $this->request = $request;
        $this->strictPolicy = false;
    }

    /**
     * Adds Content-Security-Policy header to every response.
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $responseHeaders = $event->getResponse()->headers;

        $policies = $this->policyBuilder();

        $headerValues = '';

        foreach ($policies as $policy) {
            $headerValues .= "$policy; ";
        }

        $responseHeaders->set('Content-Security-Policy', $headerValues);
    }

    public function policyBuilder()
    {
        // Routes where strict policy must be used.
        $protectedRoutes = [
            "login",
            "password_reset",
            "password_reset_request",
            "registration",
        ];

        $requestedRoute = $this->request->getMasterRequest()->get('_route');

        if (in_array($requestedRoute, $protectedRoutes)) {
            $this->setStrictPolicy(true);
        }

        switch ($this->isStrictPolicy()) {

            // Directives applied to every route except protected ones.
            case false:
                /*
                 * In dev env 'self' === localhost, NOT 127.0.0.1. You need to whitelist this IP if you dev at 127.0.0.1
                 * and not at localhost.
                 */
                $this->setMainWhitelist([
                    "'self'",
                    "127.0.0.1:8000",
                ]);

                $this->setConnectWhitelist([
                    // Add sources here
                ]);

                $this->setFormActionWhitelist([
                    // Add sources here
                ]);

                $this->setScriptWhitelist([
                    "'unsafe-inline'",
                ]);

                break;

            // Directives applied to protected routes.
            case true:
                /*
                 * In dev env 'self' === localhost, NOT 127.0.0.1. You need to whitelist this IP if you dev at 127.0.0.1
                 * and not at localhost.
                 */
                $this->setMainWhitelist([
                    "'self'",
                    "127.0.0.1:8000",
                ]);

                $this->setConnectWhitelist([
                    // Add sources here
                ]);

                $this->setFormActionWhitelist([
                    // Add sources here
                ]);

                $this->setScriptWhitelist([
                    "'unsafe-inline'",
                ]);

                break;
        }

        $mainWhitelist = implode(" ", $this->getMainWhitelist());
        $connectWhitelist = implode(" ", $this->getConnectWhitelist());
        $formActionWhitelist = implode(" ", $this->getFormActionWhitelist());
        $scriptWhitelist = implode(" ", $this->getScriptWhitelist());

        $policies = [
            'default' => "default-src $mainWhitelist",
            'connect' => "connect-src $mainWhitelist $connectWhitelist",
            'form-action' => "form-action $mainWhitelist $formActionWhitelist",
            'script' => "script-src $mainWhitelist $scriptWhitelist",
        ];

        return $policies;
    }

    /**
     * @return bool
     */
    public function isStrictPolicy(): bool
    {
        return $this->strictPolicy;
    }

    /**
     * @param bool $strictPolicy
     */
    public function setStrictPolicy(bool $strictPolicy): void
    {
        $this->strictPolicy = $strictPolicy;
    }

    /**
     * @return array
     */
    public function getMainWhitelist(): array
    {
        return $this->mainWhitelist;
    }

    /**
     * @param array $mainWhitelist
     */
    public function setMainWhitelist(array $mainWhitelist): void
    {
        $this->mainWhitelist = $mainWhitelist;
    }

    /**
     * @return array
     */
    public function getConnectWhitelist(): array
    {
        return $this->connectWhitelist;
    }

    /**
     * @param array $connectWhitelist
     */
    public function setConnectWhitelist(array $connectWhitelist): void
    {
        $this->connectWhitelist = $connectWhitelist;
    }

    /**
     * @return array
     */
    public function getFormActionWhitelist(): array
    {
        return $this->formActionWhitelist;
    }

    /**
     * @param array $formActionWhitelist
     */
    public function setFormActionWhitelist(array $formActionWhitelist): void
    {
        $this->formActionWhitelist = $formActionWhitelist;
    }

    /**
     * @return array
     */
    public function getScriptWhitelist(): array
    {
        return $this->scriptWhitelist;
    }

    /**
     * @param array $scriptWhitelist
     */
    public function setScriptWhitelist(array $scriptWhitelist): void
    {
        $this->scriptWhitelist = $scriptWhitelist;
    }
}
