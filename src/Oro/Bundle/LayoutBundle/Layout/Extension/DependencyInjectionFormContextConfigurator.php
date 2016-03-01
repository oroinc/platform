<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\DependencyInjectionFormAccessor;

/**
 * Adds FormAccessor to Context by name of form type
 */
class DependencyInjectionFormContextConfigurator implements ContextConfiguratorInterface
{
    /** @var  string */
    protected $contextOptionName;

    /** @var  string */
    protected $formServiceId;

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
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        if (!$this->formServiceId) {
            throw new \Exception('formServiceId should be specified.');
        }
        $formAccessor = new DependencyInjectionFormAccessor(
            $this->container,
            $this->formServiceId
        );

        $context->getResolver()
            ->setDefaults([$this->contextOptionName => $formAccessor]);
    }

    /**
     * @param string $contextOptionName
     */
    public function setContextOptionName($contextOptionName)
    {
        $this->contextOptionName = $contextOptionName;
    }

    /**
     * @param string $formServiceId
     */
    public function setFormServiceId($formServiceId)
    {
        $this->formServiceId = $formServiceId;
    }
}
