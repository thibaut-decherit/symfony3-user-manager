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
     * @var string
     */
    private $kernelEnvironment;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ResponseHeaderBag
     */
    private $responseHeaders;

    /**
     * @var array
     */
    private $cspConfig;

    /**
     * @var bool
     */
    private $strictPolicy;

    /**
     * @var string|null
     */
    private $cspReportUri;

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
     * Whitelist for frame-ancestors directive
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/frame-ancestors
     * @var array
     */
    private $frameAncestorsWhitelist;

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
     * @param string|null $cspReportUri
     * @param array $cspConfig
     */
    public function __construct(
        string $kernelEnvironment,
        RequestStack $requestStack,
        ResponseHeaderBag $responseHeaders,
        array $cspConfig,
        ?string $cspReportUri
    )
    {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->requestStack = $requestStack;
        $this->responseHeaders = $responseHeaders;
        $this->cspConfig = $cspConfig;
        $this->strictPolicy = false;
        $this->cspReportUri = $cspReportUri;
    }

    public function set()
    {
        $this->responseHeaders->set('Content-Security-Policy', $this->generate());
    }

    /**
     * Generates Content Security Policy header value.
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
     */
    private function generate()
    {
        // Routes where strict policy must be used.
        $protectedRoutes = $this->cspConfig['protected_routes'];

        $requestedRoute = $this->requestStack->getMasterRequest()->get('_route');

        if (in_array($requestedRoute, $protectedRoutes)) {
            $this->setStrictPolicy(true);
        }

        $laxPolicies = $this->cspConfig['policies']['lax'];
        $strictPolicies = $this->cspConfig['policies']['strict'];

        switch ($this->isStrictPolicy()) {

            // Directives applied to every route except protected ones.
            case false:
                $this->setMainWhitelist($laxPolicies['main']);

                $this->setConnectWhitelist($laxPolicies['connect']);

                $this->setFormActionWhitelist($laxPolicies['form_action']);

                $this->setFrameAncestorsWhitelist($laxPolicies['frame_ancestors']);

                $this->setScriptWhitelist($laxPolicies['script']);

                $this->setStyleWhitelist($laxPolicies['style']);

                break;

            // Directives applied to protected routes.
            case true:
                $this->setMainWhitelist($strictPolicies['main']);

                $this->setConnectWhitelist($strictPolicies['connect']);

                $this->setFormActionWhitelist($strictPolicies['form_action']);

                $this->setFrameAncestorsWhitelist($strictPolicies['frame_ancestors']);

                $this->setScriptWhitelist($strictPolicies['script']);

                $this->setStyleWhitelist($strictPolicies['style']);

                break;
        }

        $this->addDevDirectivesIfDevEnvironment();

        $mainWhitelist = implode(" ", $this->getMainWhitelist());
        $connectWhitelist = implode(" ", $this->getConnectWhitelist());
        $formActionWhitelist = implode(" ", $this->getFormActionWhitelist());
        $frameAncestorsWhitelist = implode(" ", $this->getFrameAncestorsWhitelist());
        $scriptWhitelist = implode(" ", $this->getScriptWhitelist());
        $styleWhitelist = implode(" ", $this->getStyleWhitelist());

        $policies = [
            'base-uri' => "base-uri 'self'",
            'default' => "default-src $mainWhitelist",
            'connect' => "connect-src $mainWhitelist $connectWhitelist",
            'form-action' => "form-action $mainWhitelist $formActionWhitelist",
            'frame-ancestors' => "frame-ancestors $frameAncestorsWhitelist",
            'script' => "script-src $mainWhitelist $scriptWhitelist",
            'style' => "style-src $mainWhitelist $styleWhitelist",
        ];

        $headerValue = '';

        foreach ($policies as $policy) {
            $headerValue .= "$policy; ";
        }

        // Adds violation report URI if csp_report_uri parameter is specified in config.yml.
        if (!empty($this->cspReportUri)) {
            $headerValue .= "report-uri $this->cspReportUri;";
        }

        return $headerValue;
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

        $frameAncestorsWhitelistDevDirectives = [
            // Add sources here
        ];

        $scriptWhitelistDevDirectives = [
            "'unsafe-eval'",
            "'unsafe-inline'",
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

        $this->setFrameAncestorsWhitelist(
            array_merge($this->getFrameAncestorsWhitelist(), $frameAncestorsWhitelistDevDirectives)
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
     * @return CspHeaderSetter
     */
    private function setStrictPolicy(bool $strictPolicy): CspHeaderSetter
    {
        $this->strictPolicy = $strictPolicy;
        return $this;
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
     * @return CspHeaderSetter
     */
    private function setMainWhitelist(array $mainWhitelist): CspHeaderSetter
    {
        $this->mainWhitelist = $mainWhitelist;
        return $this;
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
     * @return CspHeaderSetter
     */
    private function setConnectWhitelist(array $connectWhitelist): CspHeaderSetter
    {
        $this->connectWhitelist = $connectWhitelist;
        return $this;
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
     * @return CspHeaderSetter
     */
    private function setFormActionWhitelist(array $formActionWhitelist): CspHeaderSetter
    {
        $this->formActionWhitelist = $formActionWhitelist;
        return $this;
    }

    /**
     * @return array
     */
    private function getFrameAncestorsWhitelist(): array
    {
        return $this->frameAncestorsWhitelist;
    }

    /**
     * @param array $frameAncestorsWhitelist
     * @return CspHeaderSetter
     */
    private function setFrameAncestorsWhitelist(array $frameAncestorsWhitelist): CspHeaderSetter
    {
        $this->frameAncestorsWhitelist = $frameAncestorsWhitelist;
        return $this;
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
     * @return CspHeaderSetter
     */
    private function setScriptWhitelist(array $scriptWhitelist): CspHeaderSetter
    {
        $this->scriptWhitelist = $scriptWhitelist;
        return $this;
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
     * @return CspHeaderSetter
     */
    private function setStyleWhitelist(array $styleWhitelist): CspHeaderSetter
    {
        $this->styleWhitelist = $styleWhitelist;
        return $this;
    }
}
