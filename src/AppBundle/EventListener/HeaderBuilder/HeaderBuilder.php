<?php

namespace AppBundle\EventListener\HeaderBuilder;

use AppBundle\EventListener\HeaderBuilder\SubBuilder\CspHeaderBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class HeaderBuilder
 *
 * Adds custom headers to every response. Complex headers are set in their dedicated builder within
 * AppBundle\EventListener\HeaderBuilder\SubBuilder namespace.
 *
 * @package AppBundle\EventListener\HeaderBuilder
 */
class HeaderBuilder
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $kernelEnvironment;

    /**
     * HeaderBuilder constructor.
     * @param string $kernelEnvironment
     * @param RequestStack $requestStack
     */
    public function __construct(string $kernelEnvironment, RequestStack $requestStack)
    {
        $this->kernelEnvironment = $kernelEnvironment;
        $this->requestStack = $requestStack;
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $responseHeaders = $event->getResponse()->headers;

        $cspHeaderBuilder = new CspHeaderBuilder(
            $this->kernelEnvironment,
            $this->requestStack,
            $responseHeaders
        );

        $cspHeaderBuilder->set();
    }
}
