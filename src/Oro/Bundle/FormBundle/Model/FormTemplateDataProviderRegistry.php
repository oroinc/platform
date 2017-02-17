<?php

namespace Oro\Bundle\FormBundle\Model;

use Oro\Bundle\FormBundle\Exception\UnknownProviderException;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Registry of form template data provider services by their aliases.
 *
 * Has late construction (instantiation) mechanism (internal resolving from service container),
 * as all instances of providers not needed in a single request runtime.
 */
class FormTemplateDataProviderRegistry
{
    /** @var FormTemplateDataProviderInterface[]|string[] */
    private $formDataProvidersServices = [];

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $alias
     * @return FormTemplateDataProviderInterface
     */
    public function get($alias)
    {
        if (!isset($this->formDataProvidersServices[$alias])) {
            throw new UnknownProviderException($alias);
        }

        if (is_string($this->formDataProvidersServices[$alias])) {
            $this->formDataProvidersServices[$alias] = $this->container->get($this->formDataProvidersServices[$alias]);
        }

        if (!$this->formDataProvidersServices[$alias] instanceof FormTemplateDataProviderInterface) {
            throw new \DomainException(
                sprintf(
                    'Form data provider service `%s` with `%s` alias must implement %s.',
                    get_class($this->formDataProvidersServices[$alias]),
                    $alias,
                    FormTemplateDataProviderInterface::class
                )
            );
        }

        return $this->formDataProvidersServices[$alias];
    }

    /**
     * @param string|FormTemplateDataProviderInterface $service
     * @param string $alias
     */
    public function addProviderService($service, $alias)
    {
        if (!is_string($service) && !$service instanceof FormTemplateDataProviderInterface) {
            $type = gettype($service);
            throw new \InvalidArgumentException(
                sprintf(
                    'Can\'t add provider service.' .
                    ' The first argument MUST be service name or instance of `%s`. `%s` given.',
                    FormTemplateDataProviderInterface::class,
                    $type === 'object' ? get_class($service) : $type
                )
            );
        }
        $this->formDataProvidersServices[$alias] = $service;
    }

    /**
     * @param $alias
     *
     * @return bool
     */
    public function has($alias)
    {
        return isset($this->formDataProvidersServices[$alias]);
    }
}
