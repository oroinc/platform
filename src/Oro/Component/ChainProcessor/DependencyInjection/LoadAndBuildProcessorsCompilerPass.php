<?php

namespace Oro\Component\ChainProcessor\DependencyInjection;

use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * This DIC compiler pass can be used if you want to use DIC tags to load processors
 * and build the ProcessorBag configuration during the building of DIC instead of do it in runtime.
 */
class LoadAndBuildProcessorsCompilerPass implements CompilerPassInterface
{
    /** @var string */
    private $processorBagConfigProviderServiceId;

    /** @var string */
    private $processorTagName;

    public function __construct(string $processorBagConfigProviderServiceId, string $processorTagName)
    {
        $this->processorBagConfigProviderServiceId = $processorBagConfigProviderServiceId;
        $this->processorTagName = $processorTagName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->processorBagConfigProviderServiceId)
            && !$container->hasAlias($this->processorBagConfigProviderServiceId)
        ) {
            return;
        }

        $this->registerProcessors(
            $container,
            $container->findDefinition($this->processorBagConfigProviderServiceId)
        );
    }

    private function registerProcessors(
        ContainerBuilder $container,
        Definition $processorBagConfigProviderServiceDef
    ): void {
        $numberOfArguments = count($processorBagConfigProviderServiceDef->getArguments());
        $groups = [];
        // try get groups from the first argument of the config provider service
        if ($numberOfArguments > 0) {
            $groups = $processorBagConfigProviderServiceDef->getArgument(0);
            if (is_string($groups) && 0 === strncmp($groups, '%', 1)) {
                $groups = $container->getParameter(substr($groups, 1, -1));
            }
        }
        // convert groups from [action => [group, ...], ...] to [action => [group => priority, ...], ...]
        $groups = array_map(
            function (array $actionGroups) {
                $actionGroupsWithPriority = [];
                $priority = 0;
                foreach ($actionGroups as $group) {
                    $priority--;
                    $actionGroupsWithPriority[$group] = $priority;
                }

                return $actionGroupsWithPriority;
            },
            $groups
        );
        // load and build processors
        $processors = ProcessorsLoader::loadProcessors($container, $this->processorTagName);
        $builder = new ProcessorBagConfigBuilder($groups, $processors);
        // inject built processors to the config provider service
        if ($numberOfArguments > 1) {
            $processorBagConfigProviderServiceDef->replaceArgument(1, $builder->getAllProcessors());
        } else {
            if ($numberOfArguments === 0) {
                $processorBagConfigProviderServiceDef->addArgument([]);
            }
            $processorBagConfigProviderServiceDef->addArgument($builder->getAllProcessors());
        }
    }
}
