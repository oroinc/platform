<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Request;

use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets/sets session storage options on-the-fly.
 */
class SessionStorageOptionsManipulator
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function getOriginalSessionOptions(): array
    {
        return $this->container->getParameter('oro_security.session.storage.options');
    }

    public function getSessionOptions(): array
    {
        return $this->container->getParameter('session.storage.options');
    }

    public function setSessionOptions(array $options): void
    {
        $parametersProperty = ReflectionUtil::getProperty(new \ReflectionClass($this->container), 'parameters');
        if (null === $parametersProperty) {
            throw new \LogicException(
                sprintf(
                    'The class "%s" does not have "parameters" property.',
                    get_class($this->container)
                )
            );
        }
        $parameters = $parametersProperty->getValue($this->container);
        $parameters['session.storage.options'] = $options;
        $parametersProperty->setValue($this->container, $parameters);
    }
}
