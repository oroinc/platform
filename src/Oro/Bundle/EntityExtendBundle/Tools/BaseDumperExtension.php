<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

abstract class BaseDumperExtension extends ExtendConfigDumperExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ConfigProvider */
    protected $scopedConfigProvider;

    /** @var null|array items list that with flag enabled */
    private $flagedItems = null;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager        = $configManager;
        $this->extendConfigProvider = $configManager->getProvider('extend');

        $scopeName = $this->getScopeName();
        if ($scopeName) {
            $this->scopedConfigProvider = $configManager->getProvider($scopeName);
        }
    }

    /**
     * Return scope name extension specific to
     *
     * @return string
     */
    abstract protected function getScopeName();

    /**
     * @param string $className
     * @param string $fieldName
     * @param array  $values
     */
    protected function updateFieldConfigs($className, $fieldName, array $values)
    {
        foreach ($values as $scope => $scopeValues) {
            $configProvider = $this->configManager->getProvider($scope);
            $fieldConfig    = $configProvider->getConfig($className, $fieldName);
            foreach ($scopeValues as $code => $val) {
                $fieldConfig->set($code, $val);
            }
            $this->configManager->persist($fieldConfig);
            $this->configManager->calculateConfigChangeSet($fieldConfig);
        }
    }


    /**
     * @param string $targetEntityName
     * @param string $sourceEntityName
     * @param string $relationName
     * @param string $relationKey
     * @param bool   $isOwningSide
     * @param string $relationType
     */
    protected function addManyToOneRelation(
        $targetEntityName,
        $sourceEntityName,
        $relationName,
        $relationKey,
        $isOwningSide = false,
        $relationType = 'manyToOne'
    ) {
        $entityConfig = $this->extendConfigProvider->getConfig($targetEntityName);

        $fieldId       = false;
        $targetFieldId = false;
        $assign        = false;
        if ($isOwningSide) {
            $owner         = true;

            // update schema info
            $schema                            = $entityConfig->get('schema', false, []);
            $schema['relation'][$relationName] = $relationName;
            $entityConfig->set('schema', $schema);

            // update index info
            $index                = $entityConfig->get('index', false, []);
            $index[$relationName] = null;
            $entityConfig->set('index', $index);

            $fieldId = new FieldConfigId('extend', $targetEntityName, $relationName, $relationType);
        } else {
            $owner         = false;
            $targetFieldId = new FieldConfigId('extend', $sourceEntityName, $relationName, $relationType);
        }

        $relations = $entityConfig->get('relation', false, []);

        // add relation to config
        $relations[$relationKey] = [
            'assign'          => $assign,
            'field_id'        => $fieldId,
            'owner'           => $owner,
            'target_entity'   => $sourceEntityName,
            'target_field_id' => $targetFieldId
        ];
        $entityConfig->set('relation', $relations);

        $this->extendConfigProvider->persist($entityConfig);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param string $targetEntityName
     * @param string $relationKey
     * @param array  $values
     */
    protected function createField($className, $fieldName, $fieldType, $targetEntityName, $relationKey, $values = [])
    {
        $this->configManager->createConfigFieldModel($className, $fieldName, $fieldType);
        $classAlias = ExtendHelper::buildAssociationName($className);

        $defaultValues = [
            'extend'    => [
                'owner'         => ExtendScope::OWNER_SYSTEM,
                'state'         => ExtendScope::STATE_NEW,
                'is_extend'     => false,
                'extend'        => true,
                'is_deleted'    => false,
                'is_inverse'    => false,
                'target_entity' => $targetEntityName,
                'target_field'  => 'id',
                'relation_key'  => $relationKey,
            ],
            'entity'    => [
                'label'       => sprintf('oro.%s.%s.label', $classAlias, $fieldName),
                'description' => sprintf('oro.%s.%s.label', $classAlias, $fieldName),
            ],
            'view'      => [
                'is_displayable' => false
            ],
            'form'      => [
                'is_enabled' => true
            ],
            'dataaudit' => [
                'auditable' => false
            ]
        ];
        $values = array_merge_recursive($defaultValues, $values);

        $this->updateFieldConfigs(
            $className,
            $fieldName,
            $values
        );
    }

    /**
     * Class names list that have flag equal to value
     *
     * @param ConfigProvider $configProvider
     * @param string         $flagName
     * @param mixed          $flagValue
     *
     * @return array
     */
    protected function getClassNamesWithFlagEnabled(ConfigProvider $configProvider, $flagName, $flagValue = 1)
    {
        if (is_null($this->flagedItems)) {
            $configs           = $configProvider->getConfigs();
            $this->flagedItems = [];

            /** @var ConfigInterface $config */
            foreach ($configs as $config) {
                if ($flagValue === $config->get($flagName)) {
                    $this->flagedItems[] = $config->getId()->getClassName();
                }
            }
        }

        return $this->flagedItems;
    }

    /**
     * @param string $fromEntityClassName
     * @param string $entityClassName
     * @param string $relationFieldName
     *
     * @return string e.g. "manyToOne|Oro\Bundle\NoteBundle\Entity\Note|Oro\Bundle\UserBundle\Entity\User|user"
     */
    protected function getRelationKey($fromEntityClassName, $entityClassName, $relationFieldName)
    {
        return ExtendHelper::buildRelationKey($fromEntityClassName, $relationFieldName, 'manyToOne', $entityClassName);
    }

    /**
     * @param string $entityClassName
     *
     * @return string
     */
    protected function getRelationName($entityClassName)
    {
        return ExtendHelper::buildAssociationName($entityClassName);
    }
}
