<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Inflector\Inflector;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides virtual fields for associations to dictionary entities.
 */
class DictionaryVirtualFieldProvider implements VirtualFieldProviderInterface
{
    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;
    private TranslatorInterface $translator;
    private CacheInterface $cache;
    private array $virtualFields = [];
    private array $virtualFieldQueries = [];
    private ?array $dictionaries = null;
    private Inflector $inflector;

    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        CacheInterface $cache,
        Inflector $inflector
    ) {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->cache = $cache;
        $this->inflector = $inflector;
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
        $this->cache->clear();
    }

    /**
     * Makes sure virtual fields for the given entity were loaded
     *
     * @param string $className
     */
    private function ensureVirtualFieldsInitialized($className)
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
                    $name = $this->inflector->tableize(sprintf('%s_%s', $associationName, $fieldName));
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
        foreach ($virtualFields as $name => [$targetClass, $associationName, $fieldName, $combinedLabel]) {
            $result[$name] = [
                'query' => [
                    'select' => [
                        'expr'                => 'target.' . $fieldName,
                        'label'               => $this->resolveLabel(
                            $em,
                            $className,
                            $associationName,
                            $combinedLabel ? $name : $this->inflector->tableize($associationName)
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
    private function ensureDictionariesInitialized()
    {
        if (null === $this->dictionaries) {
            $this->dictionaries = $this->cache->get('dictionaries', function () {
                return $this->loadDictionaries();
            });
        }
    }

    /**
     * @return array [class name => [field name, ...], ...]
     */
    private function loadDictionaries()
    {
        $result = [];
        $configs = $this->configManager->getConfigs('grouping');
        foreach ($configs as $config) {
            $groups = $config->get('groups');
            if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups, true)) {
                $className = $config->getId()->getClassName();
                $fieldNames = $this->configManager->getEntityConfig('dictionary', $className)
                    ->get('virtual_fields', false, []);
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

            $entityConfig = $this->configManager->getEntityConfig('entity', $associationMapping['targetEntity']);
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
    private function getManagerForClass($className)
    {
        try {
            return $this->doctrine->getManagerForClass($className);
        } catch (\ReflectionException $ex) {
            // ignore not found exception
        }

        return null;
    }
}
