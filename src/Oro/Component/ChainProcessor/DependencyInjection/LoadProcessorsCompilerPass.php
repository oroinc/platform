<?php

namespace Oro\Component\ChainProcessor\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Component\ChainProcessor\ExpressionParser;

/**
 * This DI compiler pass can be used if you want to use DI container tags to load processors and applicable checkers.
 */
class LoadProcessorsCompilerPass implements CompilerPassInterface
{
    /** @var string */
    protected $processorBagServiceId;

    /** @var string */
    protected $processorTagName;

    /** @var string */
    protected $processorApplicableCheckerTagName;

    /**
     * @param string $processorBagServiceId
     * @param string $processorTagName
     * @param string $processorApplicableCheckerTagName
     */
    public function __construct(
        $processorBagServiceId,
        $processorTagName,
        $processorApplicableCheckerTagName = null
    ) {
        $this->processorBagServiceId = $processorBagServiceId;
        $this->processorTagName = $processorTagName;
        $this->processorApplicableCheckerTagName = $processorApplicableCheckerTagName;
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

        $processorBagServiceDef = $container->findDefinition($this->processorBagServiceId);
        $this->registerProcessors($container, $processorBagServiceDef);
        if ($this->processorApplicableCheckerTagName) {
            $this->registerApplicableCheckers($container, $processorBagServiceDef);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $processorBagServiceDef
     */
    protected function registerProcessors(ContainerBuilder $container, Definition $processorBagServiceDef)
    {
        $processors = [];
        $isDebug = $container->getParameter('kernel.debug');
        $taggedServices = $container->findTaggedServiceIds($this->processorTagName);
        foreach ($taggedServices as $id => $taggedAttributes) {
            foreach ($taggedAttributes as $attributes) {
                $action = '';
                if (!empty($attributes['action'])) {
                    $action = $attributes['action'];
                }
                unset($attributes['action']);

                $group = null;
                if (!empty($attributes['group'])) {
                    $group = $attributes['group'];
                } else {
                    unset($attributes['group']);
                }

                if (!$action && $group) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Tag attribute "group" can be used only if '
                            . 'the attribute "action" is specified. Service: "%s".',
                            $id
                        )
                    );
                }

                $priority = 0;
                if (isset($attributes['priority'])) {
                    $priority = $attributes['priority'];
                }
                if (!$isDebug) {
                    unset($attributes['priority']);
                }

                $attributes = array_map(
                    function ($val) {
                        return $this->parseProcessorAttributeValue($val);
                    },
                    $attributes
                );

                $processors[$action][$priority][] = [$id, $attributes];
            }
        }
        if (!empty($processors)) {
            $processorBagServiceDef->addMethodCall('setProcessors', [$processors]);
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function parseProcessorAttributeValue($value)
    {
        return ExpressionParser::parse($value);
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $processorBagServiceDef
     */
    protected function registerApplicableCheckers(ContainerBuilder $container, Definition $processorBagServiceDef)
    {
        $taggedServices = $container->findTaggedServiceIds($this->processorApplicableCheckerTagName);
        foreach ($taggedServices as $id => $taggedAttributes) {
            $priority = 0;
            if (isset($taggedAttributes[0]['priority'])) {
                $priority = $taggedAttributes[0]['priority'];
            }

            $processorBagServiceDef->addMethodCall('addApplicableChecker', [new Reference($id), $priority]);
        }
    }
}
