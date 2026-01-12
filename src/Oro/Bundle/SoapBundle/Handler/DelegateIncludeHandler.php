<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delegates include request handling to registered handlers based on requested includes.
 *
 * Parses the `X-Include` header to identify requested includes, routes them to appropriate
 * registered handlers, and tracks which includes were processed, unknown, or unsupported.
 * Sets response headers to communicate the status of each requested include.
 */
class DelegateIncludeHandler implements IncludeHandlerInterface
{
    /** @var array */
    protected $handlers = [];

    /** @var ContainerInterface */
    protected $container;

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

    #[\Override]
    public function supports(Context $context)
    {
        return true;
    }

    #[\Override]
    public function handle(Context $context)
    {
        $processed        = [];
        $includeRequested = explode(self::DELIMITER, $context->getRequest()->headers->get(self::HEADER_INCLUDE));
        $includeRequested = array_filter(array_map('trim', $includeRequested));
        $known            = array_intersect($includeRequested, array_keys($this->handlers));

        foreach ($known as $name) {
            $serviceId = $this->handlers[$name];
            $handler   = $this->container->get($serviceId);
            if ($handler instanceof IncludeHandlerInterface && $handler->supports($context)) {
                $handler->handle($context);
                $processed[] = $name;
            }
        }

        $unknown = array_diff($includeRequested, $known);
        if (!empty($unknown)) {
            $context->getResponse()->headers->set(self::HEADER_UNKNOWN, implode(self::DELIMITER, $unknown));
        }

        $unsupported = array_diff($known, $processed);
        if (!empty($unsupported)) {
            $context->getResponse()->headers->set(self::HEADER_UNSUPPORTED, implode(self::DELIMITER, $unsupported));
        }
    }
}
