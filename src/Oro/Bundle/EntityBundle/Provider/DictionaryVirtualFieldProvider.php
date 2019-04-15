<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides virtual fields for associations to dictionary entities.
 */
class DictionaryVirtualFieldProvider implements VirtualFieldProviderInterface
{
    /** @var ConfigProvider */
    protected $groupingConfigProvider;

    /** @var ConfigProvider */
    protected $dictionaryConfigProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var CacheProvider */
    private $cache;

    /** @var array */
    protected $virtualFields = [];

    /** @var array */
    private $virtualFieldQueries = [];

    /** @var array [class name => [field name, ...], ...] */
    protected $dictionaries;

    /**
     * @param ConfigProvider      $groupingConfigProvider
     * @param ConfigProvider      $dictionaryConfigProvider
     * @param ConfigProvider      $entityConfigProvider
     * @param ManagerRegistry     $doctrine
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigProvider $groupingConfigProvider,
        ConfigProvider $dictionaryConfigProvider,
        ConfigProvider $entityConfigProvider,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator
    ) {
        $this->groupingConfigProvider = $groupingConfigProvider;
        $this->dictionaryConfigProvider = $dictionaryConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    /**
     * @param CacheProvider $cache
     */
    public function setCache(CacheProvider $cache): void
    {
        $this->cache = $cache;
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
        $this->ensureVirtualFieldQueriesInitialized($className);

        return $this->virtualFieldQueries[$className][$fieldName]['query'];
    }

    /**
     * Removes all entries from the cache.
     */
    public function clearCache()
    {
        $this->virtualFields = [];
        $this->virtualFieldQueries = [];
        $this->dictionaries = null;
        $this->cache->deleteAll();
    }

    /**
     * Makes sure virtual fields for the given entity were loaded
     *
     * @param string $className
     */
    protected function ensureVirtualFieldsInitialized($className)
    {
        if (isset($this->virtualFields[$className])) {
            return;
        }

        $em = $this->getManagerForClass($className);
        if (!$em) {
            return;
        }

        $this->virtualFields[$className] = $this->loadVirtualFields($em->getClassMetadata($className));
    }

    /**
     * @param string $className
     */
    private function ensureVirtualFieldQueriesInitialized($className)
    {
        if (isset($this->virtualFieldQueries[$className])) {
            return;
        }

        $this->ensureVirtualFieldsInitialized($className);
        if (isset($this->virtualFields[$className])) {
            $this->virtualFieldQueries[$className] = $this->loadVirtualFieldQueries(
                $className,
                $this->virtualFields[$className]
            );
        }
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return array [virtualFieldName => [targetClass, associationName, fieldName, combinedLabel], ...]
     */
    private function loadVirtualFields(ClassMetadata $metadata)
    {
        $this->ensureDictionariesInitialized();

        $result = [];
        $associationMappings = $metadata->getAssociationMappings();
        foreach ($associationMappings as $associationName => $associationMapping) {
            $targetClass = $associationMapping['targetEntity'];
            if (isset($this->dictionaries[$targetClass])) {
                $fieldNames = $this->dictionaries[$targetClass];
                $combinedLabel = count($fieldNames) > 1;
                foreach ($fieldNames as $fieldName) {
                    $name = Inflector::tableize(sprintf('%s_%s', $associationName, $fieldName));
                    $result[$name] = [$targetClass, $associationName, $fieldName, $combinedLabel];
                }
            }
        }

        return $result;
    }

    /**
     * @param string $className
     * @param array  $virtualFields [virtualFieldName => [targetClass, associationName, fieldName, combinedLabel], ...]
     *
     * @return array [virtualFieldName => query, ...]
     */
    private function loadVirtualFieldQueries($className, array $virtualFields)
    {
        $result = [];
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($className);
        foreach ($virtualFields as $name => list($targetClass, $associationName, $fieldName, $combinedLabel)) {
            $result[$name] = [
                'query' => [
                    'select' => [
                        'expr'                => 'target.' . $fieldName,
                        'label'               => $this->resolveLabel(
                            $em,
                            $className,
                            $associationName,
                            $combinedLabel ? $name : Inflector::tableize($associationName)
                        ),
                        'return_type'         => 'dictionary',
                        'related_entity_name' => $targetClass
                    ],
                    'join'   => [
                        'left' => [
                            [
                                'join'  => 'entity.' . $associationName,
                                'alias' => 'target'
                            ]
                        ]
                    ]
                ]
            ];
        }

        return $result;
    }

    /**
     * Makes sure metadata for dictionary entities were loaded
     */
    protected function ensureDictionariesInitialized()
    {
        if (null === $this->dictionaries) {
            $this->dictionaries = $this->cache->fetch('dictionaries');
            if (false === $this->dictionaries) {
                $this->dictionaries = $this->loadDictionaries();
                $this->cache->save('dictionaries', $this->dictionaries);
            }
        }
    }

    /**
     * @return array [class name => [field name, ...], ...]
     */
    private function loadDictionaries()
    {
        $result = [];
        $configs = $this->groupingConfigProvider->getConfigs();
        foreach ($configs as $config) {
            $groups = $config->get('groups');
            if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups, true)) {
                $className = $config->getId()->getClassName();
                $fieldNames = $this->dictionaryConfigProvider->hasConfig($className)
                    ? $this->dictionaryConfigProvider->getConfig($className)->get('virtual_fields', false, [])
                    : [];
                if (empty($fieldNames)) {
                    $metadata = $this->getManagerForClass($className)->getClassMetadata($className);
                    $allFieldNames = $metadata->getFieldNames();
                    foreach ($allFieldNames as $fieldName) {
                        if (!$metadata->isIdentifier($fieldName)) {
                            $fieldNames[] = $fieldName;
                        }
                    }
                }
                $result[$className] = $fieldNames;
            }
        }

        return $result;
    }

    /**
     * @param EntityManagerInterface $em
     * @param string                 $className
     * @param string                 $associationName
     * @param string                 $labelKey
     *
     * @return string
     */
    private function resolveLabel(EntityManagerInterface $em, $className, $associationName, $labelKey)
    {
        $label = ConfigHelper::getTranslationKey('entity', 'label', $className, $labelKey);
        if ($this->translator->trans($label) === $label) {
            $metadata = $em->getClassMetadata($className);
            $associationMapping = $metadata->getAssociationMapping($associationName);

            $entityConfig = $this->entityConfigProvider->getConfig($associationMapping['targetEntity']);
            $multiple = $associationMapping['type'] & ClassMetadataInfo::TO_MANY;
            $labelOption = $multiple ? 'plural_label' : 'label';
            if ($entityConfig->has($labelOption)) {
                $label = $entityConfig->get($labelOption);
            }
        }

        return $label;
    }

    /**
     * Gets doctrine entity manager for the given class
     *
     * @param string $className
     *
     * @return EntityManager|null
     */
    protected function getManagerForClass($className)
    {
        try {
            return $this->doctrine->getManagerForClass($className);
        } catch (\ReflectionException $ex) {
            // ignore not found exception
        }

        return null;
    }
}
