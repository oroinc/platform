<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects and register access rules.
 */
class AccessRulesPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    private const EXECUTOR_SERVICE_ID = 'oro_security.access_rule_executor';
    private const RULE_TAG_NAME       = 'oro_security.access_rule';
    private const PRIORITY_ATTRIBUTE  = 'priority';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $rules = [];
        $services = [];
        $taggedServices = $container->findTaggedServiceIds(self::RULE_TAG_NAME, true);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $priority = 0;
                if (array_key_exists(self::PRIORITY_ATTRIBUTE, $tagAttributes)) {
                    $priority = $tagAttributes[self::PRIORITY_ATTRIBUTE];
                    unset($tagAttributes[self::PRIORITY_ATTRIBUTE]);
                }
                $rules[$priority][] = [$id, $tagAttributes];
            }
        }
        if ($rules) {
            $rules = $this->sortByPriorityAndFlatten($rules);
        }

        $container->findDefinition(self::EXECUTOR_SERVICE_ID)
            ->setArgument(0, $rules)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
