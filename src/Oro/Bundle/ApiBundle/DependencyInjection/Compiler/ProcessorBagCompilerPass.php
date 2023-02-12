<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Component\ChainProcessor\AbstractMatcher as Matcher;
use Oro\Component\ChainProcessor\DependencyInjection\ProcessorsLoader;
use Oro\Component\ChainProcessor\ProcessorBagActionConfigProvider;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds all registered API processors to the processor bag service.
 * * By performance reasons "customize_loaded_data" processors with "collection" attribute equals to TRUE
 *   are moved to "collection" group and other processors to "item" group.
 *   The "collection" attribute is removed.
 * * For "customize_loaded_data" processors that do not have "identifier_only" attribute,
 *   it is added with FALSE value. If such processor has this attribute and its value is NULL,
 *   the attribute is removed.
 * * Makes the "event" attribute for "customize_form_data" processors mandatory to prevent potential logical errors.
 * * By performance reasons "customize_form_data" processors are grouped by event.
 *   The "event" attribute is removed.
 * * By performance reasons "identifier_fields_only" config extra for "get_config" processors
 *   is moved to "identifier_fields_only" attribute.
 * * By performance reasons "normalize_value" processors are grouped by data type.
 *   The "dataType" attribute is removed.
 *
 * @see \Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Handler\EntityHandler
 * @see \Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProcessorBagCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const PROCESSOR_BAG_CONFIG_PROVIDER_SERVICE_ID = 'oro_api.processor_bag_config_provider';

    private const CUSTOMIZE_LOADED_DATA_ACTION = 'customize_loaded_data';
    private const CUSTOMIZE_FORM_DATA_ACTION = 'customize_form_data';
    private const GET_CONFIG_ACTION = 'get_config';
    private const GET_METADATA_ACTION = 'get_metadata';
    private const NORMALIZE_VALUE_ACTION = 'normalize_value';

    private const DATA_TYPE_ATTRIBUTE = 'dataType';
    private const IDENTIFIER_ONLY_ATTRIBUTE = 'identifier_only';
    private const EXTRA_ATTRIBUTE = 'extra';
    private const GROUP_ATTRIBUTE = 'group';
    private const COLLECTION_ATTRIBUTE = 'collection';
    private const EVENT_ATTRIBUTE = 'event';

    private const ITEM_GROUP = 'item';
    private const COLLECTION_GROUP = 'collection';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $groups = [];
        $config = DependencyInjectionUtil::getConfig($container);
        foreach ($config['actions'] as $action => $actionConfig) {
            if (isset($actionConfig['processing_groups'])) {
                foreach ($actionConfig['processing_groups'] as $group => $groupConfig) {
                    $groups[$action][$group] = $this->getPriorityAttribute($groupConfig);
                }
            }
        }
        $groups[self::CUSTOMIZE_LOADED_DATA_ACTION] = [self::ITEM_GROUP => 0, self::COLLECTION_GROUP => -1];
        $groups[self::CUSTOMIZE_FORM_DATA_ACTION] = [
            CustomizeFormDataContext::EVENT_PRE_SUBMIT      => 0,
            CustomizeFormDataContext::EVENT_SUBMIT          => -1,
            CustomizeFormDataContext::EVENT_POST_SUBMIT     => -2,
            CustomizeFormDataContext::EVENT_PRE_VALIDATE    => -3,
            CustomizeFormDataContext::EVENT_POST_VALIDATE   => -4,
            CustomizeFormDataContext::EVENT_PRE_FLUSH_DATA  => -5,
            CustomizeFormDataContext::EVENT_POST_FLUSH_DATA => -6,
            CustomizeFormDataContext::EVENT_POST_SAVE_DATA  => -7
        ];
        $processors = ProcessorsLoader::loadProcessors($container, DependencyInjectionUtil::PROCESSOR_TAG);
        $builder = new ProcessorBagConfigBuilder($groups, $processors);
        $loadedGroups = $builder->getAllGroups();
        $loadedProcessors = $this->normalizeProcessors($builder->getAllProcessors(), $groups);
        if (!empty($loadedProcessors[self::NORMALIZE_VALUE_ACTION])) {
            $loadedGroups[self::NORMALIZE_VALUE_ACTION] = $this->extractGroups(
                $loadedProcessors[self::NORMALIZE_VALUE_ACTION]
            );
        }
        $container->getDefinition(self::PROCESSOR_BAG_CONFIG_PROVIDER_SERVICE_ID)
            ->replaceArgument(0, array_keys($loadedProcessors))
            ->replaceArgument(
                1,
                ServiceLocatorTagPass::register(
                    $container,
                    $this->registerProcessorBagConfigProvider($container, $loadedGroups, $loadedProcessors)
                )
            );
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $groups     [action => [group, ...], ...]
     * @param array            $processors [action => [[processor id, [attr name => attr value, ...]], ...], ...]
     *
     * @return Reference[] [action => action config provider, ...]
     */
    private function registerProcessorBagConfigProvider(
        ContainerBuilder $container,
        array $groups,
        array $processors
    ): array {
        $referenceMap = [];
        foreach ($processors as $action => $actionProcessors) {
            $serviceId = self::PROCESSOR_BAG_CONFIG_PROVIDER_SERVICE_ID . '.' . $action;
            $container->register($serviceId, ProcessorBagActionConfigProvider::class)
                ->setPublic(false)
                ->setArguments([$groups[$action] ?? [], $actionProcessors]);
            $referenceMap[$action] = new Reference($serviceId);
        }

        return $referenceMap;
    }

    /**
     * @param array $allProcessors [action => [[processor id, [attribute name => attribute value, ...]], ...], ...]
     * @param array $allGroups     [action => [group name => group priority, ...], ...]
     *
     * @return array [action => [[processor id, [attribute name => attribute value, ...]], ...], ...]
     */
    private function normalizeProcessors(array $allProcessors, array $allGroups): array
    {
        if (!empty($allProcessors[self::CUSTOMIZE_LOADED_DATA_ACTION])) {
            $allProcessors[self::CUSTOMIZE_LOADED_DATA_ACTION] = $this->normalizeCustomizeLoadedDataProcessors(
                $allProcessors[self::CUSTOMIZE_LOADED_DATA_ACTION]
            );
        }
        if (!empty($allProcessors[self::CUSTOMIZE_FORM_DATA_ACTION])) {
            $allProcessors[self::CUSTOMIZE_FORM_DATA_ACTION] = $this->normalizeCustomizeFormDataProcessors(
                $allProcessors[self::CUSTOMIZE_FORM_DATA_ACTION],
                array_keys($allGroups[self::CUSTOMIZE_FORM_DATA_ACTION])
            );
        }
        if (!empty($allProcessors[self::GET_CONFIG_ACTION])) {
            $allProcessors[self::GET_CONFIG_ACTION] = $this->normalizeGetConfigProcessors(
                $allProcessors[self::GET_CONFIG_ACTION]
            );
        }
        if (!empty($allProcessors[self::GET_METADATA_ACTION])) {
            $allProcessors[self::GET_METADATA_ACTION] = $this->normalizeGetMetadataProcessors(
                $allProcessors[self::GET_METADATA_ACTION]
            );
        }
        if (!empty($allProcessors[self::NORMALIZE_VALUE_ACTION])) {
            $allProcessors[self::NORMALIZE_VALUE_ACTION] = $this->normalizeNormalizeValueProcessors(
                $allProcessors[self::NORMALIZE_VALUE_ACTION]
            );
        }

        ksort($allProcessors);

        return $allProcessors;
    }

    /**
     * @param array $processors
     *
     * @return string[]
     */
    private function extractGroups(array $processors): array
    {
        $groupMap = [];
        foreach ($processors as $item) {
            if (!empty($item[1][self::GROUP_ATTRIBUTE])) {
                $group = $item[1][self::GROUP_ATTRIBUTE];
                if (!isset($groupMap[$group])) {
                    $groupMap[$group] = true;
                }
            }
        }

        return array_keys($groupMap);
    }

    /**
     * Normalizes processors for "customize_loaded_data" action
     * and split them to "item" and "collection" groups.
     */
    private function normalizeCustomizeLoadedDataProcessors(array $processors): array
    {
        $itemProcessors = [];
        $collectionProcessors = [];
        foreach ($processors as $item) {
            $this->assertNoGroupAttribute(
                $item[0],
                $item[1],
                self::CUSTOMIZE_LOADED_DATA_ACTION,
                self::COLLECTION_ATTRIBUTE
            );
            $isCollectionProcessor = \array_key_exists(self::COLLECTION_ATTRIBUTE, $item[1])
                && $item[1][self::COLLECTION_ATTRIBUTE];
            unset($item[1][self::COLLECTION_ATTRIBUTE]);
            if ($isCollectionProcessor) {
                $item[1][self::GROUP_ATTRIBUTE] = self::COLLECTION_GROUP;
                // "identifier_only" attribute is not supported for collections
                if (\array_key_exists(self::IDENTIFIER_ONLY_ATTRIBUTE, $item[1])) {
                    throw new LogicException(sprintf(
                        'The "%s" processor uses the "%s" tag attribute that is not supported'
                        . ' in case the "%s" tag attribute equals to true.',
                        $item[0],
                        self::IDENTIFIER_ONLY_ATTRIBUTE,
                        self::COLLECTION_ATTRIBUTE
                    ));
                }
                $collectionProcessors[] = $item;
            } else {
                $item[1][self::GROUP_ATTRIBUTE] = self::ITEM_GROUP;
                // normalize "identifier_only" attribute
                if (!\array_key_exists(self::IDENTIFIER_ONLY_ATTRIBUTE, $item[1])) {
                    // add "identifier_only" attribute to the beginning of an attributes array,
                    // it will give a small performance gain at the runtime
                    $item[1] = [self::IDENTIFIER_ONLY_ATTRIBUTE => false] + $item[1];
                } elseif (null === $item[1][self::IDENTIFIER_ONLY_ATTRIBUTE]) {
                    unset($item[1][self::IDENTIFIER_ONLY_ATTRIBUTE]);
                }
                $itemProcessors[] = $item;
            }
        }

        return array_merge($itemProcessors, $collectionProcessors);
    }

    /**
     * Normalizes processors for "customize_form_data" action
     * and split them to groups by events.
     *
     * @param array    $processors
     * @param string[] $allEvents
     *
     * @return array
     */
    private function normalizeCustomizeFormDataProcessors(array $processors, array $allEvents): array
    {
        $groupedProcessors = [];
        foreach ($processors as $item) {
            $this->assertNoGroupAttribute(
                $item[0],
                $item[1],
                self::CUSTOMIZE_FORM_DATA_ACTION,
                self::EVENT_ATTRIBUTE
            );
            $events = $this->parseCustomizeFormDataEventAttribute($item[0], $item[1], $allEvents);
            unset($item[1][self::EVENT_ATTRIBUTE]);
            foreach ($events as $event) {
                $item[1][self::GROUP_ATTRIBUTE] = $event;
                $groupedProcessors[$event][] = $item;
            }
        }

        $sortedByEventProcessors = [];
        foreach ($allEvents as $event) {
            if (isset($groupedProcessors[$event])) {
                $sortedByEventProcessors[] = $groupedProcessors[$event];
            }
        }

        return array_merge(...$sortedByEventProcessors);
    }

    /**
     * Normalizes processors for "get_config" action.
     * Moves "identifier_fields_only" config extra to "identifier_fields_only" attribute.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function normalizeGetConfigProcessors(array $processors): array
    {
        foreach ($processors as $key => $item) {
            $this->assertExtraAttribute($item[0], $item[1]);
            if (\array_key_exists(self::EXTRA_ATTRIBUTE, $item[1])) {
                $identifierFieldsOnly = null;
                if (\is_array($item[1][self::EXTRA_ATTRIBUTE])) {
                    if (Matcher::OPERATOR_AND === key($item[1][self::EXTRA_ATTRIBUTE])) {
                        foreach (current($item[1][self::EXTRA_ATTRIBUTE]) as $k => $v) {
                            if (\is_array($v)) {
                                if (FilterIdentifierFieldsConfigExtra::NAME === current($v)) {
                                    $identifierFieldsOnly = false;
                                }
                            } elseif (FilterIdentifierFieldsConfigExtra::NAME === $v) {
                                $identifierFieldsOnly = true;
                            }
                            if (null !== $identifierFieldsOnly) {
                                unset($item[1][self::EXTRA_ATTRIBUTE][Matcher::OPERATOR_AND][$k]);
                                if (\count($item[1][self::EXTRA_ATTRIBUTE][Matcher::OPERATOR_AND]) === 1) {
                                    $processors[$key][1][self::EXTRA_ATTRIBUTE] = reset(
                                        $item[1][self::EXTRA_ATTRIBUTE][Matcher::OPERATOR_AND]
                                    );
                                } else {
                                    $processors[$key][1][self::EXTRA_ATTRIBUTE] = [
                                        Matcher::OPERATOR_AND => array_values(
                                            $item[1][self::EXTRA_ATTRIBUTE][Matcher::OPERATOR_AND]
                                        )
                                    ];
                                }
                                break;
                            }
                        }
                    } elseif (FilterIdentifierFieldsConfigExtra::NAME === current($item[1][self::EXTRA_ATTRIBUTE])) {
                        $identifierFieldsOnly = false;
                        unset($processors[$key][1][self::EXTRA_ATTRIBUTE]);
                    }
                } elseif (FilterIdentifierFieldsConfigExtra::NAME === $item[1][self::EXTRA_ATTRIBUTE]) {
                    $identifierFieldsOnly = true;
                }
                if (null !== $identifierFieldsOnly) {
                    $processors[$key][1] =
                        [FilterIdentifierFieldsConfigExtra::NAME => $identifierFieldsOnly] + $processors[$key][1];
                }
            }
        }

        return $processors;
    }

    /**
     * Normalizes processors for "get_metadata" action.
     */
    private function normalizeGetMetadataProcessors(array $processors): array
    {
        foreach ($processors as $item) {
            $this->assertExtraAttribute($item[0], $item[1]);
        }

        return $processors;
    }

    /**
     * Validates processors for "normalize_value" action.
     */
    private function normalizeNormalizeValueProcessors(array $processors): array
    {
        $result = [];
        foreach ($processors as $item) {
            $dataTypes = $this->parseDataTypeAttribute($item[0], $item[1], self::NORMALIZE_VALUE_ACTION);
            unset($item[1][self::DATA_TYPE_ATTRIBUTE]);
            foreach ($dataTypes as $dataType) {
                $item[1][self::GROUP_ATTRIBUTE] = $dataType;
                $result[] = $item;
            }
        }

        return $result;
    }

    private function assertNoGroupAttribute(
        string $processorId,
        array $attributes,
        string $action,
        string $expectedAttributeName
    ): void {
        if (\array_key_exists(self::GROUP_ATTRIBUTE, $attributes)) {
            throw new LogicException(sprintf(
                'The "%s" processor uses the "%s" tag attribute that is not allowed'
                . ' for the "%s" action. Use "%s" tag attribute instead.',
                $processorId,
                self::GROUP_ATTRIBUTE,
                $action,
                $expectedAttributeName
            ));
        }
    }

    private function assertExtraAttribute(string $processorId, array $attributes): void
    {
        if (\array_key_exists(self::EXTRA_ATTRIBUTE, $attributes)
            && \is_array($attributes[self::EXTRA_ATTRIBUTE])
            && Matcher::OPERATOR_OR === key($attributes[self::EXTRA_ATTRIBUTE])
        ) {
            throw new LogicException(sprintf(
                'The "%s" processor uses the "%s" tag attribute with "%s" operator that is not allowed.'
                . ' Only "%s" and "%s" operators are allowed for this attribute.',
                $processorId,
                self::EXTRA_ATTRIBUTE,
                Matcher::OPERATOR_OR,
                Matcher::OPERATOR_AND,
                Matcher::OPERATOR_NOT
            ));
        }
    }

    /**
     * @param string $processorId
     * @param array  $attributes
     * @param string $action
     *
     * @return string[]
     */
    private function parseDataTypeAttribute(string $processorId, array $attributes, string $action): array
    {
        if (!\array_key_exists(self::DATA_TYPE_ATTRIBUTE, $attributes)) {
            throw new LogicException(sprintf(
                'The "%s" processor for the "%s" action must have the "%s" tag attribute.',
                $processorId,
                $action,
                self::DATA_TYPE_ATTRIBUTE
            ));
        }

        return $this->parseAttributeWhenOnlyOrExpressionIsAllowed(
            $processorId,
            $attributes,
            self::DATA_TYPE_ATTRIBUTE,
            $action,
            'a data type or data types'
        );
    }

    /**
     * @param string   $processorId
     * @param array    $attributes
     * @param string[] $allEvents
     *
     * @return array
     */
    private function parseCustomizeFormDataEventAttribute(
        string $processorId,
        array $attributes,
        array $allEvents
    ): array {
        if (!\array_key_exists(self::EVENT_ATTRIBUTE, $attributes)) {
            throw new LogicException(sprintf(
                'The "%s" tag attribute is mandatory for the "%s" processor.'
                . ' Use "%s: %s" when your processor should be executed for all events.',
                self::EVENT_ATTRIBUTE,
                $processorId,
                self::EVENT_ATTRIBUTE,
                implode(Matcher::OPERATOR_OR, $allEvents)
            ));
        }

        $events = $this->parseAttributeWhenOnlyOrExpressionIsAllowed(
            $processorId,
            $attributes,
            self::EVENT_ATTRIBUTE,
            self::CUSTOMIZE_FORM_DATA_ACTION,
            'an event name or event names'
        );
        foreach ($events as $event) {
            if (!\in_array($event, $allEvents, true)) {
                throw new LogicException(sprintf(
                    'The "%s" processor has the "%s" tag attribute with a value that is not valid'
                    . ' for the "%s" action. The event "%s" is not supported. The supported events: %s.',
                    $processorId,
                    self::EVENT_ATTRIBUTE,
                    self::CUSTOMIZE_FORM_DATA_ACTION,
                    $event,
                    implode(', ', $allEvents)
                ));
            }
        }

        return $events;
    }

    /**
     * @param string $processorId
     * @param array  $attributes
     * @param string $attributeName
     * @param string $action
     * @param string $description
     *
     * @return string[]
     */
    private function parseAttributeWhenOnlyOrExpressionIsAllowed(
        string $processorId,
        array $attributes,
        string $attributeName,
        string $action,
        string $description
    ): array {
        $value = $attributes[$attributeName];
        if (\is_string($value)) {
            return [$value];
        }
        if (!\is_array($value) || key($value) !== Matcher::OPERATOR_OR) {
            throw $this->createInvalidAttributeWhenOnlyOrExpressionIsAllowedException(
                $processorId,
                $attributeName,
                $action,
                $description
            );
        }

        $items = reset($value);
        foreach ($items as $item) {
            if (!\is_string($item)) {
                throw $this->createInvalidAttributeWhenOnlyOrExpressionIsAllowedException(
                    $processorId,
                    $attributeName,
                    $action,
                    $description
                );
            }
        }

        return $items;
    }

    private function createInvalidAttributeWhenOnlyOrExpressionIsAllowedException(
        string $processorId,
        string $attributeName,
        string $action,
        string $description
    ): LogicException {
        throw new LogicException(sprintf(
            'The "%s" processor has the "%s" tag attribute with a value that is not valid'
            . ' for the "%s" action. The value of this attribute must be %s delimited be "%s".',
            $processorId,
            $attributeName,
            $action,
            $description,
            Matcher::OPERATOR_OR
        ));
    }
}
