<?php

namespace AppBundle\EventListener\ResponseHeaderSetter\DynamicResponseHeaderSetter;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class CspHeaderSetter
 *
 * Adds Content Security Policy header to a response.
 * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
 *
 * @package AppBundle\EventListener\ResponseHeaderSetter\DynamicResponseHeaderSetter
 */
class CspHeaderSetter
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ResponseHeaderBag
     */
    private $responseHeaders;

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
     * CspHeaderSetter constructor.
     * @param string $kernelEnvironment
     * @param RequestStack $requestStack
     * @param ResponseHeaderBag $responseHeaders
     */
    public function __construct(string $kernelEnvironment, RequestStack $requestStack, ResponseHeaderBag $responseHeaders)
    {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->requestStack = $requestStack;
        $this->strictPolicy = false;
        $this->responseHeaders = $responseHeaders;
    }

    public function set()
    {
        $headerValues = $this->build();

        $this->responseHeaders->set('Content-Security-Policy', $headerValues);
    }

    /**
     * Builds Content Security Policy header values.
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
     *
     */
    private function build()
    {
        // Routes where strict policy must be used.
        $protectedRoutes = [
            "login",
            "password_reset",
            "password_reset_request",
            "registration",
        ];

        $requestStackedRoute = $this->requestStack->getMasterRequest()->get('_route');

        if (in_array($requestStackedRoute, $protectedRoutes)) {
            $this->setStrictPolicy(true);
        }

        switch ($this->isStrictPolicy()) {

            // Directives applied to every route except protected ones.
            case false:
                $this->setMainWhitelist([
                    "'self'",
                ]);

                $this->setConnectWhitelist([
                    "https://api.pwnedpasswords.com",
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
                    "https://api.pwnedpasswords.com",
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

        $headerValues = '';

        foreach ($policies as $policy) {
            $headerValues .= "$policy; ";
        }

        return $headerValues;
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
    private function isStrictPolicy(): bool
    {
        return $this->strictPolicy;
    }

    /**
     * @param bool $strictPolicy
     */
    private function setStrictPolicy(bool $strictPolicy): void
    {
        $this->strictPolicy = $strictPolicy;
    }

    /**
     * @return array
     */
    private function getMainWhitelist(): array
    {
        return $this->mainWhitelist;
    }

    /**
     * @param array $mainWhitelist
     */
    private function setMainWhitelist(array $mainWhitelist): void
    {
        $this->mainWhitelist = $mainWhitelist;
    }

    /**
     * @return array
     */
    private function getConnectWhitelist(): array
    {
        return $this->connectWhitelist;
    }

    /**
     * @param array $connectWhitelist
     */
    private function setConnectWhitelist(array $connectWhitelist): void
    {
        $this->connectWhitelist = $connectWhitelist;
    }

    /**
     * @return array
     */
    private function getFormActionWhitelist(): array
    {
        return $this->formActionWhitelist;
    }

    /**
     * @param array $formActionWhitelist
     */
    private function setFormActionWhitelist(array $formActionWhitelist): void
    {
        $this->formActionWhitelist = $formActionWhitelist;
    }

    /**
     * @return array
     */
    private function getScriptWhitelist(): array
    {
        return $this->scriptWhitelist;
    }

    /**
     * @param array $scriptWhitelist
     */
    private function setScriptWhitelist(array $scriptWhitelist): void
    {
        $this->scriptWhitelist = $scriptWhitelist;
    }

    /**
     * @return array
     */
    private function getStyleWhitelist(): array
    {
        return $this->styleWhitelist;
    }

    /**
     * @param array $styleWhitelist
     */
    private function setStyleWhitelist(array $styleWhitelist): void
    {
        $this->styleWhitelist = $styleWhitelist;
    }
}
