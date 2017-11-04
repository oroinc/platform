<?php

namespace Oro\Component\ChainProcessor\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * This DIC compiler pass can be used if you want to use DIC tags to load processors.
 */
class LoadProcessorsCompilerPass implements CompilerPassInterface
{
    /** @var string */
    protected $processorBagConfigBuilderServiceId;

    /** @var string */
    protected $processorTagName;

    /**
     * @param string $processorBagConfigBuilderServiceId
     * @param string $processorTagName
     */
    public function __construct($processorBagConfigBuilderServiceId, $processorTagName)
    {
        $this->processorBagConfigBuilderServiceId = $processorBagConfigBuilderServiceId;
        $this->processorTagName = $processorTagName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->processorBagConfigBuilderServiceId)
            && !$container->hasAlias($this->processorBagConfigBuilderServiceId)
        ) {
            return;
        }

        $this->registerProcessors(
            $container,
            $container->findDefinition($this->processorBagConfigBuilderServiceId)
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $processorBagConfigBuilderServiceDef
     */
    protected function registerProcessors(
        ContainerBuilder $container,
        Definition $processorBagConfigBuilderServiceDef
    ) {
        // load processors
        $processors = ProcessorsLoader::loadProcessors($container, $this->processorTagName);
        // inject processors to the config builder service
        if (!empty($processors)) {
            $numberOfArguments = count($processorBagConfigBuilderServiceDef->getArguments());
            if ($numberOfArguments > 1) {
                $processorBagConfigBuilderServiceDef->replaceArgument(1, $processors);
            } else {
                if ($numberOfArguments === 0) {
                    $processorBagConfigBuilderServiceDef->addArgument([]);
                }
                $processorBagConfigBuilderServiceDef->addArgument($processors);
            }
        }
    }
}
