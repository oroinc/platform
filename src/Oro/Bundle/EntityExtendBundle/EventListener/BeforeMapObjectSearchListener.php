<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Event\BeforeMapObjectEvent;

class BeforeMapObjectSearchListener
{
    const TITLE_FIELDS_PATH = 'title_fields';
    const FIELDS_PATH = 'fields';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param BeforeMapObjectEvent $event
     */
    public function prepareEntityMapEvent(BeforeMapObjectEvent $event)
    {
        $object        = $event->getEntity();
        $className     = ClassUtils::getRealClass($object);
        $searchConfigs = $this->configManager->getConfigs('search', $className);

        $mapConfig = $this->checkEntityIsSearchable($event->getMappingConfig(), $className);

        if (isset($mapConfig[$className])) {
            foreach ($searchConfigs as $searchConfig) {
                /** @var FieldConfigId $fieldId */
                $fieldId = $searchConfig->getId();

                if (!$fieldId instanceof FieldConfigId) {
                    continue;
                }
                $fieldName = $fieldId->getFieldName();

                if ($searchConfig->is('title_field', true)) {
                    $mapConfig[$className][self::TITLE_FIELDS_PATH] = array_merge(
                        $mapConfig[$className][self::TITLE_FIELDS_PATH],
                        [$fieldName]
                    );
                }

                if ($searchConfig->is('searchable', true)) {
                    $fieldType = $this->transformCustomType($fieldId->getFieldType());
                    if (
                        in_array(
                            $fieldType,
                            [
                                Indexer::RELATION_ONE_TO_ONE,
                                Indexer::RELATION_MANY_TO_ONE
                            ]
                        )
                    ) {
                        $config = $this->configManager->getConfig($this->configManager->getId('extend', $className, $fieldName));
                        $targetEntity = $config->get('target_entity');
                        $targetField = $config->get('target_field');
                        $targetType = $this->transformCustomType($this->configManager->getId('extend', $targetEntity, $targetField)->getFieldType());
                        $field = [
                            'name'          => $fieldName,
                            'relation_type' => $fieldType,
                            'relation_fields' => [
                                [
                                    'name'          => $targetField,
                                    'target_type'   => $targetType,
                                    'target_fields' => [$fieldName . '-' . $targetField]
                                ]
                            ]
                        ];
                    } elseif(
                        in_array(
                            $fieldType,
                            [

                                Indexer::RELATION_MANY_TO_MANY,
                                Indexer::RELATION_ONE_TO_MANY
                            ]
                        )
                    ) {
                        $config = $this->configManager->getConfig($this->configManager->getId('extend', $className, $fieldName));
                        $targetEntity = $config->get('target_entity');


                        $targetFields = array_unique(array_merge($config->get('target_grid'), $config->get('target_title'), $config->get('target_detailed')));
                        $fields = [];
                        foreach ($targetFields as $targetField) {
                            $targetType = $this->transformCustomType($this->configManager->getId('extend', $targetEntity, $targetField)->getFieldType());
                            $fields[] = [
                                'name'          => $targetField,
                                'target_type'   => $targetType,
                                'target_fields' => [$fieldName . '-' . $targetField]
                            ];
                        }

                        $field = [
                            'name'          => $fieldName,
                            'relation_type' => $fieldType,
                            'relation_fields' => $fields
                        ];

                    } else {
                        $field = [
                            'name'          => $fieldName,
                            'target_type'   => $fieldType,
                            'target_fields' => [$fieldName]
                        ];
                    }

                    $mapConfig[$className][self::FIELDS_PATH][] = $field;
                }
            }
        }
        $event->setMappingConfig($mapConfig);
    }

    /**
     * @param array  $mapConfig
     * @param string $className
     * @return array
     */
    protected function checkEntityIsSearchable(array $mapConfig, $className)
    {
        $config = $this->configManager->getConfig($this->configManager->getId('extend', $className));
        if ($config->get('owner') === 'Custom' && $config->get('state') === 'Active') {
            if ($this->configManager->getConfig($this->configManager->getId('search', $className))->is('searchable', true)) {
                $mapConfig[$className] = [
                    'alias' => $config->get('schema')['doctrine'][$className]['table'],
                    'label' => $this->configManager->getConfig($this->configManager->getId('entity', $className))->get('label'),
                    'search_template' => 'OroEntityExtendBundle:Search:result.html.twig',
                    'route' => [
                        'name' => 'oro_entity_view',
                        'parameters' => [
                            'id' => 'id',
                            'entityName' => str_replace('\\', '_', $className)
                        ]
                    ]
                ];
            }
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
            'multiEnum'                => 'text',
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
