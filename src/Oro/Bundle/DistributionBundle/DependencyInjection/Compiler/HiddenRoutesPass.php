<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection\Compiler;

use Oro\Component\Routing\Matcher\PhpMatcherDumper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;

/**
 * Replaces `router.default` service options with PhpMatcherDumper instead of CompiledUrlMatcherDumper
 */
class HiddenRoutesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('router.default');
        $options = $definition->getArgument(2);

        $newClass = $this->getNewRoutingMatcherDumperClass($options['matcher_dumper_class'] ?? null);
        if ($newClass) {
            $options['matcher_dumper_class'] = $newClass;
            $definition->setArgument(2, $options);
        }
    }

    private function getNewRoutingMatcherDumperClass(?string $currentClass): ?string
    {
        return CompiledUrlMatcherDumper::class === $currentClass ? PhpMatcherDumper::class : null;
    }
}
