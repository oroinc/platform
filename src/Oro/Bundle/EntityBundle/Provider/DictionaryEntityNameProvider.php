<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Component\DoctrineUtils\ORM\DqlUtil;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The name provider for dictionaries.
 */
class DictionaryEntityNameProvider implements EntityNameProviderInterface
{
    private const DEFAULT_REPRESENTATION_FIELD = 'label';

    /** @var ConfigManager */
    private $configManager;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param ConfigManager             $configManager
     * @param ManagerRegistry           $doctrine
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        $fieldNames = $this->getRepresentationFieldNames(ClassUtils::getClass($entity));
        if (!$fieldNames) {
            return false;
        }

        if (count($fieldNames) > 1) {
            return implode(' ', \array_map(
                function ($fieldName) use ($entity) {
                    return $this->propertyAccessor->getValue($entity, $fieldName);
                },
                $fieldNames
            ));
        }

        return $this->propertyAccessor->getValue($entity, $fieldNames[0]);
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        $fieldNames = $this->getRepresentationFieldNames($className);
        if (!$fieldNames) {
            return false;
        }

        if (count($fieldNames) > 1) {
            return DqlUtil::buildConcatExpr(\array_map(
                function ($fieldName) use ($alias) {
                    return sprintf('%s.%s', $alias, $fieldName);
                },
                $fieldNames
            ));
        }

        return sprintf('%s.%s', $alias, $fieldNames[0]);
    }

    /**
     * @param string $className
     *
     * @return string[]|null
     */
    private function getRepresentationFieldNames($className)
    {
        if (!$this->isDictionary($className)) {
            return null;
        }

        $entityConfig = $this->configManager->getEntityConfig('dictionary', $className);
        $representationFieldName = $entityConfig->get('representation_field');
        if ($representationFieldName) {
            return [$representationFieldName];
        }
        $searchFieldNames = $entityConfig->get('search_fields');
        if ($searchFieldNames) {
            return $searchFieldNames;
        }
        if ($this->hasField($className, self::DEFAULT_REPRESENTATION_FIELD)) {
            return [self::DEFAULT_REPRESENTATION_FIELD];
        }

        return null;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    private function isDictionary($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return false;
        }

        $groups = $this->configManager->getEntityConfig('grouping', $className)->get('groups');

        return !empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups, true);
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    private function hasField($className, $fieldName)
    {
        $manager = $this->doctrine->getManagerForClass($className);
        if (null === $manager) {
            return false;
        }

        return $manager->getClassMetadata($className)->hasField($fieldName);
    }
}
