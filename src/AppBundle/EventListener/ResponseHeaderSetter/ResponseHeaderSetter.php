<?php

namespace AppBundle\EventListener\ResponseHeaderSetter;

use AppBundle\EventListener\ResponseHeaderSetter\DynamicResponseHeaderSetter\CspHeaderSetter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class ResponseHeaderSetter
 *
 * Adds custom headers to every response. Dynamic headers are generated and set in their dedicated class within
 * AppBundle\EventListener\ResponseHeaderSetter\DynamicResponseHeaderSetter namespace.
 *
 * @package AppBundle\EventListener\ResponseHeaderSetter
 */
class ResponseHeaderSetter
{
    /**
     * @var string
     */
    private $kernelEnvironment;

    /**
     * @var array
     */
    private $simpleHeaders;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var array
     */
    private $cspConfig;

    /**
     * @var string|null
     */
    private $cspReportUri;

    /**
     * ResponseHeaderSetter constructor.
     * @param string $kernelEnvironment
     * @param array $simpleHeaders
     * @param RequestStack $requestStack
     * @param array $cspConfig
     * @param string|null $cspReportUri
     */
    public function __construct(
        string $kernelEnvironment,
        array $simpleHeaders,
        RequestStack $requestStack,
        array $cspConfig,
        ?string $cspReportUri = null
    )
    {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->simpleHeaders = $simpleHeaders;
        $this->requestStack = $requestStack;
        $this->cspConfig = $cspConfig;
        $this->cspReportUri = $cspReportUri;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $responseHeaders = $event->getResponse()->headers;

        $this->setDynamicHeaders($responseHeaders);
        $this->setStaticHeaders($responseHeaders);
    }

    /**
     * Sets headers requiring a dedicated class to generate them according to specific parameters (e.g. app environment,
     * requested route...).
     *
     * @param ResponseHeaderBag $responseHeaders
     */
    private function setDynamicHeaders(ResponseHeaderBag $responseHeaders)
    {
        (new CspHeaderSetter(
            $this->kernelEnvironment,
            $this->requestStack,
            $responseHeaders,
            $this->cspConfig,
            $this->cspReportUri
        ))->set();
    }

    /**
     * Sets headers specified in config.yml.
     *
     * @param ResponseHeaderBag $responseHeaders
     */
    private function setStaticHeaders(ResponseHeaderBag $responseHeaders)
    {
        foreach ($this->simpleHeaders as $key => $value) {
            $responseHeaders->set($key, $value);
        }
    }
}
