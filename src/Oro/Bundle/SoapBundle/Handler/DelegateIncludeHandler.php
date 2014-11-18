<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DelegateIncludeHandler implements IncludeHandlerInterface
{
    const HEADER_INCLUDE     = 'X-Include';
    const HEADER_UNSUPPORTED = 'X-Include-Unsupported';
    const HEADER_UNKNOWN     = 'X-Include-Unknown';
    const DELIMITER          = ';';

    /** @var array */
    protected $handlers;

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Collecting handlers that registered with oro_soap.include_handler tag
     *
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
    public function supports($object, array $context)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($object, array $context, Request $request, Response $response)
    {
        $processed        = [];
        $includeRequested = explode(self::DELIMITER, $request->headers->get(self::HEADER_INCLUDE));
        $includeRequested = array_filter(array_map('trim', $includeRequested));
        $known            = array_intersect($includeRequested, array_keys($this->handlers));
        $unknown          = array_diff($includeRequested, $known);

        foreach ($known as $name) {
            $serviceId = $this->handlers[$name];
            $handler   = $this->container->get($serviceId);
            if ($handler instanceof IncludeHandlerInterface && $handler->supports($object, $context)) {
                $handler->handle($object, $context, $request, $response);
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
