<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SearchBundle\Engine\Indexer;

class BeforeMapObjectSearchListener
{
    const TITLE_FIELDS_PATH = 'title_fields';
    const FIELDS_PATH = 'fields';

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Process custom entities and fields. If entity or field marked as searchable - config of this custom
     *  entity or field will bw added to the main search map.
     *
     * @param SearchMappingCollectEvent $event
     */
    public function prepareEntityMapEvent(SearchMappingCollectEvent $event)
    {
        $mapConfig = $event->getMappingConfig();
        $configs   = $this->configManager->getProvider('extend')->getConfigs();
        foreach ($configs as $config) {
            if ($config->is('is_extend') && $config->get('state') === ExtendScope::STATE_ACTIVE) {
                $className     = $config->getId()->getClassName();
                $searchConfigs = $this->configManager->getConfigs('search', $className);

                $mapConfig = $this->checkEntityMapping($mapConfig, $config);
                if (isset($mapConfig[$className])
                    && (
                        $config->get('owner') === 'System'
                        || ($config->get('owner') === 'Custom' && $config->get('state') === 'Active'
                            && $this->configManager->getProvider('search')->getConfig($className)->is('searchable')
                        )
                    )
                ) {
                    foreach ($searchConfigs as $searchConfig) {
                        /** @var FieldConfigId $fieldId */
                        $fieldId = $searchConfig->getId();
                        if (!$fieldId instanceof FieldConfigId) {
                            continue;
                        }

                        $this->processTitles($mapConfig, $searchConfig, $className, $fieldId->getFieldName());
                        $this->processFields($mapConfig, $searchConfig, $fieldId, $className);
                    }
                }
            }
        }
        $event->setMappingConfig($mapConfig);
    }

    /**
     * Process field
     *
     * @param array           $mapConfig
     * @param ConfigInterface $searchConfig
     * @param FieldConfigId   $fieldId
     * @param string          $className
     */
    protected function processFields(&$mapConfig, ConfigInterface $searchConfig, FieldConfigId $fieldId, $className)
    {
        $fieldName = $fieldId->getFieldName();
        if ($searchConfig->is('searchable')) {
            $fieldType = $this->transformCustomType($fieldId->getFieldType());
            if (in_array(
                    $fieldType,
                    [
                        Indexer::RELATION_ONE_TO_ONE,
                        Indexer::RELATION_MANY_TO_ONE
                    ]
                )
            ) {
                $config       = $this->configManager->getConfig(
                    $this->configManager->getId('extend', $className, $fieldName)
                );
                $targetEntity = $config->get('target_entity');
                $targetField  = $config->get('target_field');
                $targetType   = $this->transformCustomType(
                    $this->configManager->getId('extend', $targetEntity, $targetField)->getFieldType()
                );
                $field        = [
                    'name'            => $fieldName,
                    'relation_type'   => $fieldType,
                    'relation_fields' => [
                        [
                            'name'          => $targetField,
                            'target_type'   => $targetType,
                            'target_fields' => [strtolower($fieldName . '_' . $targetField)]
                        ]
                    ]
                ];
            } elseif (in_array(
                    $fieldType,
                    [

                        Indexer::RELATION_MANY_TO_MANY,
                        Indexer::RELATION_ONE_TO_MANY
                    ]
                )
            ) {
                $config       = $this->configManager->getConfig(
                    $this->configManager->getId('extend', $className, $fieldName)
                );
                $targetEntity = $config->get('target_entity');


                $targetFields = array_unique(array_merge($config->get('target_grid'),
                    $config->get('target_title'), $config->get('target_detailed')));
                $fields       = [];
                foreach ($targetFields as $targetField) {
                    $targetType = $this->transformCustomType(
                        $this->configManager->getId('extend', $targetEntity, $targetField)->getFieldType()
                    );
                    $fields[] = [
                        'name'          => $targetField,
                        'target_type'   => $targetType,
                        'target_fields' => [strtolower($fieldName . '_' . $targetField)]
                    ];
                }

                $field = [
                    'name'            => $fieldName,
                    'relation_type'   => $fieldType,
                    'relation_fields' => $fields
                ];

            } else {
                $field = [
                    'name'          => $fieldName,
                    'target_type'   => $fieldType,
                    'target_fields' => [strtolower($fieldName)]
                ];
            }
            $mapConfig[$className][self::FIELDS_PATH][] = $field;
        }
    }

    /**
     * Check if field marked as title_field, add this field to titles
     *
     * @param array           $mapConfig
     * @param ConfigInterface $searchConfig
     * @param string          $className
     * @param string          $fieldName
     */
    protected function processTitles(&$mapConfig, ConfigInterface $searchConfig, $className, $fieldName)
    {
        if ($searchConfig->is('title_field')) {
            $mapConfig[$className][self::TITLE_FIELDS_PATH] = array_merge(
                $mapConfig[$className][self::TITLE_FIELDS_PATH],
                [$fieldName]
            );
        }
    }

    /**
     * Check custom entity. If it searchable - add mapping skeleton
     *
     * @param array           $mapConfig
     * @param ConfigInterface $config
     * @return array
     */
    protected function checkEntityMapping(array $mapConfig, ConfigInterface $config)
    {
        $className = $config->getId()->getClassName();
        if ($config->get('owner') === 'Custom' && $config->get('state') === 'Active') {
            $label                 = $this->configManager->getProvider('entity')->getConfig($className)->get('label');
            $mapConfig[$className] = [
                'alias'                 => $config->get('schema')['doctrine'][$className]['table'],
                'label'                 => $label,
                self::TITLE_FIELDS_PATH => [],
                'route'                 => [
                    'name'       => 'oro_entity_view',
                    'parameters' => [
                        'id'         => 'id',
                        'entityName' => '@' . str_replace('\\', '_', $className) . '@'
                    ]
                ],
                'search_template'       => 'OroEntityExtendBundle:Search:result.html.twig',
                self::FIELDS_PATH       => [],
                'mode'                  => 'normal'
            ];
        }

        return $mapConfig;
    }

    /**
     * Transform field type to search engine type
     *
     * @param string $type
     * @return string
     */
    protected function transformCustomType($type)
    {
        $customTypesMap = [
            'string'                   => 'text',
            'text'                     => 'text',
            'money'                    => 'decimal',
            'percent'                  => 'decimal',
            'enum'                     => 'text',
            'multiEnum'                => Indexer::RELATION_MANY_TO_MANY,
            'bigint'                   => 'text',
            'integer'                  => 'integer',
            'smallint'                 => 'integer',
            'datetime'                 => 'datetime',
            'date'                     => 'datetime',
            'float'                    => 'decimal',
            'decimal'                  => 'decimal',
            RelationType::ONE_TO_MANY  => Indexer::RELATION_ONE_TO_MANY,
            RelationType::MANY_TO_ONE  => Indexer::RELATION_MANY_TO_ONE,
            RelationType::MANY_TO_MANY => Indexer::RELATION_MANY_TO_MANY,
        ];

        return isset($customTypesMap[$type]) ? $customTypesMap[$type] : $type;
    }
}
