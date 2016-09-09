<?php

namespace Oro\Bundle\LocaleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DefaultFallbackExtensionPass implements CompilerPassInterface
{
    const GENERATOR_EXTENSION_NAME = 'oro_locale.entity_generator.extension';

    /**
     * @var array Array of classes and fields
     */
    protected $classes;

    public function __construct(array $classes = [])
    {
        $this->classes = $classes;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$this->classes) {
            return;
        }

        if (!$container->hasDefinition(self::GENERATOR_EXTENSION_NAME)) {
            return;
        }

        $generator = $container->getDefinition(self::GENERATOR_EXTENSION_NAME);

        foreach ($this->classes as $class => $fields) {
            $generator->addMethodCall('addDefaultMethodFields', [$class, $fields]);
        }
    }
}
