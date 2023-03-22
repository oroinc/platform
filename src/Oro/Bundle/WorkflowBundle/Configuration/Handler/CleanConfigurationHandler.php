<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

use Oro\Bundle\EntityExtendBundle\EntityExtend\PropertyAccessorWithDotArraySyntax;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Clean workflow configuration handler.
 */
class CleanConfigurationHandler implements ConfigurationHandlerInterface
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var PropertyAccessorWithDotArraySyntax */
    private $propertyAccessor;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $configuration)
    {
        if (!$this->isApplicable() || !$configuration) {
            return $configuration;
        }

        $configuration = $this->cleanConfiguration(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITIONS,
            [
                new PropertyPath('[form_options][form_init]')
            ],
            true
        );
        $configuration = $this->cleanConfiguration(
            $configuration,
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS,
            [
                new PropertyPath('[preactions]'),
                new PropertyPath('[preconditions]'),
                new PropertyPath('[conditions]'),
                new PropertyPath('[actions]')
            ]
        );

        return $configuration;
    }

    /**
     * @param array $configuration
     * @param string $nodeName
     * @param array $paths
     * @param bool $remove
     * @return array
     */
    private function cleanConfiguration(array $configuration, $nodeName, array $paths, $remove = false)
    {
        $propertyAccessor = $this->getPropertyAccessor();

        $nodeConfig = array_map(
            function (array $transition) use ($propertyAccessor, $paths, $remove) {
                foreach ($paths as $path) {
                    if ($remove) {
                        $propertyAccessor->remove($transition, $path);
                    } else {
                        $propertyAccessor->setValue($transition, $path, []);
                    }
                }

                return $transition;
            },
            $this->getConfigurationOption($configuration, $nodeName, [])
        );

        return array_merge($configuration, [$nodeName => $nodeConfig]);
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getConfigurationOption(array $options, $key, $default = null)
    {
        return array_key_exists($key, $options) ? $options[$key] : $default;
    }

    /**
     * @return bool
     */
    protected function isApplicable()
    {
        return $this->requestStack->getCurrentRequest() !== null;
    }

    /**
     * @return PropertyAccessorWithDotArraySyntax
     */
    private function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax();
        }

        return $this->propertyAccessor;
    }
}
