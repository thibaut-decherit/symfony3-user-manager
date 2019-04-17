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
     * @var string
     */
    private $kernelEnvironment;

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
     * Whitelist for style-src directive
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/style-src
     * @var array
     */
    private $styleWhitelist;

    /**
     * CspHeaderBuilder constructor.
     * @param string $kernelEnvironment
     * @param RequestStack $request
     */
    public function __construct(string $kernelEnvironment, RequestStack $request)
    {
        $this->kernelEnvironment = $kernelEnvironment;
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
                $this->setMainWhitelist([
                    "'self'",
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

                $this->setStyleWhitelist([
                    // Add sources here
                ]);

                break;

            // Directives applied to protected routes.
            case true:
                $this->setMainWhitelist([
                    "'self'",
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

                $this->setStyleWhitelist([
                    // Add sources here
                ]);

                break;
        }

        $this->addDevDirectivesIfDevEnvironment();

        $mainWhitelist = implode(" ", $this->getMainWhitelist());
        $connectWhitelist = implode(" ", $this->getConnectWhitelist());
        $formActionWhitelist = implode(" ", $this->getFormActionWhitelist());
        $scriptWhitelist = implode(" ", $this->getScriptWhitelist());
        $styleWhitelist = implode(" ", $this->getStyleWhitelist());

        $policies = [
            'default' => "default-src $mainWhitelist",
            'connect' => "connect-src $mainWhitelist $connectWhitelist",
            'form-action' => "form-action $mainWhitelist $formActionWhitelist",
            'script' => "script-src $mainWhitelist $scriptWhitelist",
            'style' => "style-src $mainWhitelist $styleWhitelist",
        ];

        return $policies;
    }

    // Adds dev only directives if the app runs in dev environment.
    private function addDevDirectivesIfDevEnvironment()
    {
        if ($this->kernelEnvironment !== 'dev') {
            return;
        }

        /*
         * In dev env 'self' === localhost, NOT 127.0.0.1. You need to whitelist this IP if you dev at 127.0.0.1
         * and not at localhost.
         */
        $mainWhitelistDevDirectives = [
            "127.0.0.1:8000",
        ];

        $connectWhitelistDevDirectives = [
            // Add sources here
        ];

        $formActionWhitelistDevDirectives = [
            // Add sources here
        ];

        $scriptWhitelistDevDirectives = [
            "'unsafe-eval'",
        ];

        $styleWhitelistDevDirectives = [
            "'unsafe-inline'",
        ];

        $this->setMainWhitelist(
            array_merge($this->getMainWhitelist(), $mainWhitelistDevDirectives)
        );

        $this->setConnectWhitelist(
            array_merge($this->getConnectWhitelist(), $connectWhitelistDevDirectives)
        );

        $this->setFormActionWhitelist(
            array_merge($this->getFormActionWhitelist(), $formActionWhitelistDevDirectives)
        );

        $this->setScriptWhitelist(
            array_merge($this->getScriptWhitelist(), $scriptWhitelistDevDirectives)
        );

        $this->setStyleWhitelist(
            array_merge($this->getStyleWhitelist(), $styleWhitelistDevDirectives)
        );
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

    /**
     * @return array
     */
    public function getStyleWhitelist(): array
    {
        return $this->styleWhitelist;
    }

    /**
     * @param array $styleWhitelist
     */
    public function setStyleWhitelist(array $styleWhitelist): void
    {
        $this->styleWhitelist = $styleWhitelist;
    }
}
