<?php

namespace Oro\Component\ChainProcessor\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This DIC compiler pass can be used if you want to use DIC tags to load applicable checkers.
 */
class LoadApplicableCheckersCompilerPass implements CompilerPassInterface
{
    /** @var string */
    protected $processorBagServiceId;

    /** @var string */
    protected $applicableCheckerTagName;

    /**
     * @param string $processorBagServiceId
     * @param string $applicableCheckerTagName
     */
    public function __construct($processorBagServiceId, $applicableCheckerTagName)
    {
        $this->processorBagServiceId = $processorBagServiceId;
        $this->applicableCheckerTagName = $applicableCheckerTagName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->processorBagServiceId)
            && !$container->hasAlias($this->processorBagServiceId)
        ) {
            return;
        }

        $this->registerApplicableCheckers(
            $container,
            $container->findDefinition($this->processorBagServiceId)
        );
    }

    protected function registerApplicableCheckers(ContainerBuilder $container, Definition $processorBagServiceDef)
    {
        $taggedServices = $container->findTaggedServiceIds($this->applicableCheckerTagName);
        foreach ($taggedServices as $id => $taggedAttributes) {
            $processorBagServiceDef->addMethodCall(
                'addApplicableChecker',
                [new Reference($id), $taggedAttributes[0]['priority'] ?? 0]
            );
        }
    }
}
