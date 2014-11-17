<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DelegateHandler implements IncludeHandlerInterface
{
    const HEADER_INCLUDE     = 'X-Include';
    const HEADER_UNSUPPORTED = 'X-Include-Unsupported';
    const HEADER_UNKNOWN     = 'X-Include-Unknown';
    const DELIMITER          = '; ';

    /** @var array */
    protected $handlers;

    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $name
     * @param string $serviceId
     */
    public function registerHandler($name, $serviceId)
    {
        $this->handlers[$name] = $serviceId;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($object)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($object, Request $request, Response $response)
    {
        $processed        = [];
        $includeRequested = explode(self::DELIMITER, $response->headers->get(self::HEADER_INCLUDE));
        $known            = array_intersect($includeRequested, array_keys($this->handlers));
        $unknown          = array_diff($includeRequested, $known);

        foreach ($known as $name) {
            $serviceId = $this->handlers[$name];
            $handler   = $this->container->get($serviceId);
            if ($handler instanceof IncludeHandlerInterface && $handler->supports($object)) {
                $handler->handle($object, $request, $response);
                $processed[] = $name;
            }
        }

        if (!empty($unknown)) {
            $response->headers->set(self::HEADER_UNKNOWN, implode(self::DELIMITER, $unknown));
        }

        $unsupported = array_diff($known, $processed);
        if (!empty($unsupported)) {
            $response->headers->set(self::HEADER_UNSUPPORTED, implode(self::DELIMITER, $unsupported));
        }
    }
}
