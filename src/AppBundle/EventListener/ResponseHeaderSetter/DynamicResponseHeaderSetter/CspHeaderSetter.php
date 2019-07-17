<?php

namespace AppBundle\EventListener\ResponseHeaderSetter\DynamicResponseHeaderSetter;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\RouterInterface;

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
     * @var RouterInterface
     */
    private $router;

    /**
     * @var array
     */
    private $directives;

    /**
     * CspHeaderSetter constructor.
     * @param string $kernelEnvironment
     * @param RequestStack $requestStack
     * @param ResponseHeaderBag $responseHeaders
     * @param array $cspConfig
     * @param RouterInterface $router
     */
    public function __construct(
        string $kernelEnvironment,
        RequestStack $requestStack,
        ResponseHeaderBag $responseHeaders,
        array $cspConfig,
        RouterInterface $router
    )
    {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->requestStack = $requestStack;
        $this->responseHeaders = $responseHeaders;
        $this->cspConfig = $cspConfig;
        $this->router = $router;
        $this->directives = [];
    }

    public function set()
    {
        $this->responseHeaders->set('Content-Security-Policy', $this->generate());
    }

    /**
     * Generates Content Security Policy header value.
     * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
     * @return string
     */
    private function generate()
    {
        // Routes where strict policy must be used.
        $protectedRoutes = $this->cspConfig['protected_routes'];

        $requestedRoute = $this->requestStack->getMasterRequest()->get('_route');

        if (in_array($requestedRoute, $protectedRoutes)) {
            $this->setDirectives($this->cspConfig['directives']['strict']);
        } else {
            $this->setDirectives($this->cspConfig['directives']['lax']);
        }

        $this->addReportUriIfSet();

        $this->addDevDirectivesIfDevEnvironment();

        $headerValue = '';

        foreach ($this->directives as $directiveName => $directiveContent) {
            $directiveContentString = implode(" ", $directiveContent);

            $directive = "$directiveName $directiveContentString; ";

            $headerValue .= $directive;
        }

        return $headerValue;
    }

    // Adds dev only directives if the app runs in dev environment.
    private function addDevDirectivesIfDevEnvironment()
    {
        if ($this->kernelEnvironment !== 'dev') {
            return;
        }

        $directives = $this->getDirectives();

        /*
         * In dev env 'self' === http://localhost:port, NOT 127.0.0.1. You need to whitelist this IP if you dev at
         * http://127.0.0.1:port and not at http://localhost:port.
         */
        $uriArray = explode('/', $this->requestStack->getCurrentRequest()->getUri());
        array_splice($uriArray, 3);
        $baseUrl = implode('/', $uriArray);

        $directives['default-src'][] = $baseUrl;
        $directives['connect-src'][] = $baseUrl;
        $directives['form-action'][] = $baseUrl;
        $directives['script-src'][] = "$baseUrl 'self' 'unsafe-eval' 'unsafe-inline'";
        $directives['style-src'][] = "$baseUrl 'self' 'unsafe-inline'";

        $this->setDirectives($directives);
    }

    private function addReportUriIfSet()
    {
        if (!isset($this->cspConfig['report_uri'])) {
            return;
        }

        $reportUri = '';

        switch ($this->cspConfig['report_uri']['method']) {
            case 'plain':
                $reportUri = $this->cspConfig['report_uri']['data'];

                break;

            case 'match':
                $reportUri = $this->router->generate($this->cspConfig['report_uri']['data']);

                break;
        }

        $directives = $this->getDirectives();

        $directives['report-uri'][] = $reportUri;

        $this->setDirectives($directives);
    }

    /**
     * @return array
     */
    private function getDirectives(): array
    {
        return $this->directives;
    }

    /**
     * @param array $directives
     * @return CspHeaderSetter
     */
    private function setDirectives(array $directives): CspHeaderSetter
    {
        $this->directives = $directives;
        return $this;
    }
}
