<?php

namespace Oro\Component\ChainProcessor\DependencyInjection;

use Oro\Component\ChainProcessor\MatchApplicableChecker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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
        $this->processorBagServiceId             = $processorBagServiceId;
        $this->processorTagName                  = $processorTagName;
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
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function registerProcessors(ContainerBuilder $container, Definition $processorBagServiceDef)
    {
        $isDebug        = $container->getParameter('kernel.debug');
        $taggedServices = $container->findTaggedServiceIds($this->processorTagName);
        foreach ($taggedServices as $id => $taggedAttributes) {
            foreach ($taggedAttributes as $attributes) {
                $action   = !empty($attributes['action']) ? $attributes['action'] : null;
                $group    = !empty($attributes['group']) ? $attributes['group'] : null;
                $priority = isset($attributes['priority']) ? $attributes['priority'] : 0;

                if (null === $action && null !== $group) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Tag attribute "group" can be used only if '
                            . 'the attribute "action" is specified. Service: "%s".',
                            $id
                        )
                    );
                }

                unset($attributes['action'], $attributes['group']);
                if (!$isDebug) {
                    unset($attributes['priority']);
                }
                $attributes = array_map(
                    function ($val) {
                        return $this->parseProcessorAttributeValue($val);
                    },
                    $attributes
                );

                $processorBagServiceDef->addMethodCall('addProcessor', [$id, $attributes, $action, $group, $priority]);
            }
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function parseProcessorAttributeValue($value)
    {
        if (is_string($value)) {
            $operator = null;
            if (strpos($value, MatchApplicableChecker::OPERATOR_AND)) {
                $operator = MatchApplicableChecker::OPERATOR_AND;
                $value = explode(MatchApplicableChecker::OPERATOR_AND, $value);
            } elseif (strpos($value, MatchApplicableChecker::OPERATOR_OR)) {
                $operator = MatchApplicableChecker::OPERATOR_OR;
                $value = explode(MatchApplicableChecker::OPERATOR_OR, $value);
            } elseif (0 === strpos($value, MatchApplicableChecker::OPERATOR_NOT)) {
                $value = [MatchApplicableChecker::OPERATOR_NOT => substr($value, 1)];
            }
            if (null !== $operator) {
                return [
                    $operator => array_map(
                        function ($val) {
                            return 0 === strpos($val, MatchApplicableChecker::OPERATOR_NOT)
                                ? [MatchApplicableChecker::OPERATOR_NOT => substr($val, 1)]
                                : $val;
                        },
                        $value
                    )
                ];
            }
        }

        return $value;
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $processorBagServiceDef
     */
    protected function registerApplicableCheckers(ContainerBuilder $container, Definition $processorBagServiceDef)
    {
        $taggedServices = $container->findTaggedServiceIds($this->processorApplicableCheckerTagName);
        foreach ($taggedServices as $id => $taggedAttributes) {
            $priority = isset($taggedAttributes[0]['priority']) ? $taggedAttributes[0]['priority'] : 0;

            $processorBagServiceDef->addMethodCall('addApplicableChecker', [new Reference($id), $priority]);
        }
    }
}
