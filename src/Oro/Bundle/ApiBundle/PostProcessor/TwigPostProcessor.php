<?php

namespace Oro\Bundle\ApiBundle\PostProcessor;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

/**
 * Applies a TWIG template to a field value.
 */
class TwigPostProcessor implements PostProcessorInterface, ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function process(mixed $value, array $options): mixed
    {
        if (null === $value) {
            return null;
        }

        $twigContent = $options;
        $twigContent['value'] = $value;
        unset($twigContent['template']);

        return $this->getTwig()->render($options['template'], $twigContent);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        /**
         * Inject TWIG service via the service locator because it is optional and not all API requests use it,
         * This solution improves performance of API requests that do not need TWIG.
         */
        return [
            Environment::class
        ];
    }

    private function getTwig(): Environment
    {
        return $this->container->get(Environment::class);
    }
}
