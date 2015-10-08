<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

/**
 * Implements VirtualFieldProviderInterface for relations to dictionary entities
 */
class DictionaryVirtualFieldProvider implements VirtualFieldProviderInterface
{
    /** @var ConfigProvider */
    protected $groupingConfigProvider;

    /** @var ConfigProvider */
    protected $dictionaryConfigProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $virtualFields = [];

    /**
     * @var array
     *      key   = class name
     *      value = array of fields
     *          key =   fieldName
     *          value = fieldType
     */
    protected $dictionaries;

    /**
     * Constructor
     *
     * @param ConfigProvider  $groupingConfigProvider
     * @param ConfigProvider  $dictionaryConfigProvider
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        ConfigProvider $groupingConfigProvider,
        ConfigProvider $dictionaryConfigProvider,
        ManagerRegistry $doctrine
    ) {
        $this->groupingConfigProvider   = $groupingConfigProvider;
        $this->dictionaryConfigProvider = $dictionaryConfigProvider;
        $this->doctrine                 = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFields($className)
    {
        $this->ensureVirtualFieldsInitialized($className);

        return isset($this->virtualFields[$className])
            ? array_keys($this->virtualFields[$className])
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized($className);

        return isset($this->virtualFields[$className][$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        $this->ensureVirtualFieldsInitialized($className);

        return $this->virtualFields[$className][$fieldName]['query'];
    }

    /**
     * Makes sure virtual fields for the given entity were loaded
     *
     * @param string $className
     */
    protected function ensureVirtualFieldsInitialized($className)
    {
        if (!isset($this->virtualFields[$className])) {
            $this->ensureDictionariesInitialized();
            $this->virtualFields[$className] = [];

            $metadata         = $this->getManagerForClass($className)->getClassMetadata($className);
            $associationNames = $metadata->getAssociationNames();
            foreach ($associationNames as $associationName) {
                $targetClassName = $metadata->getAssociationTargetClass($associationName);
                if (isset($this->dictionaries[$targetClassName])) {
                    $fields = $this->dictionaries[$targetClassName];
                    $isCombinedLabelName = count($fields) > 1;
                    $fieldNames = array_keys($fields);
                    $target = Inflector::tableize(uniqid(sprintf('t_%s', $associationName), false));
                    foreach ($fieldNames as $fieldName) {
                        $virtualFieldName = Inflector::tableize(sprintf('%s_%s', $associationName, $fieldName));
                        $fieldName = Inflector::tableize($fieldName);
                        $label = $isCombinedLabelName
                            ? $virtualFieldName
                            : Inflector::tableize($associationName);
                        $label = ConfigHelper::getTranslationKey('entity', 'label', $className, $label);
                        $this->virtualFields[$className][$virtualFieldName] = [
                            'query' => [
                                'select' => [
                                    'expr' => sprintf('%s.%s', $target, $fieldName),
                                    'return_type' => GroupingScope::GROUP_DICTIONARY,
                                    'related_entity_name' => $targetClassName,
                                    'label' => $label
                                ],
                                'join' => [
                                    'left' => [
                                        [
                                            'join' => sprintf('entity.%s', $associationName),
                                            'alias' => $target
                                        ]
                                    ]
                                ]
                            ]
                        ];
                    }
                }
            }
        }
    }

    /**
     * Makes sure metadata for dictionary entities were loaded
     */
    protected function ensureDictionariesInitialized()
    {
        if (null === $this->dictionaries) {
            $this->dictionaries = [];
            $configs            = $this->groupingConfigProvider->getConfigs();
            foreach ($configs as $config) {
                $groups = $config->get('groups');
                if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups, true)) {
                    $className  = $config->getId()->getClassName();
                    $metadata   = $this->getManagerForClass($className)->getClassMetadata($className);
                    $fields     = [];
                    $fieldNames = $this->dictionaryConfigProvider->hasConfig($className)
                        ? $this->dictionaryConfigProvider->getConfig($className)->get('virtual_fields', false, [])
                        : [];
                    if (!empty($fieldNames)) {
                        foreach ($fieldNames as $fieldName) {
                            $fields[$fieldName] = $metadata->getTypeOfField($fieldName);
                        }
                    } else {
                        $fieldNames = $metadata->getFieldNames();
                        foreach ($fieldNames as $fieldName) {
                            if (!$metadata->isIdentifier($fieldName)) {
                                $fields[$fieldName] = $metadata->getTypeOfField($fieldName);
                            }
                        }
                    }
                    $this->dictionaries[$className] = $fields;
                }
            }
        }
    }

    /**
     * Gets doctrine entity manager for the given class
     *
     * @param string $className
     * @return EntityManager
     * @throws InvalidEntityException
     */
    protected function getManagerForClass($className)
    {
        $manager = null;
        try {
            $manager = $this->doctrine->getManagerForClass($className);
        } catch (\ReflectionException $ex) {
            // ignore not found exception
        }
        if (!$manager) {
            throw new InvalidEntityException(sprintf('The "%s" entity was not found.', $className));
        }

        return $manager;
    }
}
