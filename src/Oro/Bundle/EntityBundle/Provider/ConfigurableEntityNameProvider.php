<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Inflector\Inflector;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;

/**
 * The resolver for entity names(titles) that works based on the "oro_entity.entity_name_representation" configuration.
 */
class ConfigurableEntityNameProvider implements EntityNameProviderInterface
{
    /** @var array [entity class => [format => [field name, ...], ...], ...] */
    private array $fields;
    private ManagerRegistry $doctrine;
    private Inflector $inflector;

    public function __construct(
        array $fields,
        ManagerRegistry $doctrine,
        Inflector $inflector
    ) {
        $this->fields = $fields;
        $this->doctrine = $doctrine;
        $this->inflector = $inflector;
    }

    /**
     * {@inheritDoc}
     */
    public function getName($format, $locale, $entity)
    {
        $className = ClassUtils::getClass($entity);
        if (!isset($this->fields[$className][$format])) {
            return false;
        }

        $name = $this->getConstructedName($entity, $this->fields[$className][$format]);
        if ($name) {
            return $name;
        }

        // field value is empty, try with id
        $idFiledName = $this->getSingleIdFieldName($className);
        if ($idFiledName) {
            $idValue = $this->getFieldValue($entity, $idFiledName);

            return null === $idValue || \is_string($idValue)
                ? $idValue
                : (string)$idValue;
        }

        // no identifier column
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!isset($this->fields[$className][$format])) {
            return false;
        }

        $fieldNames = [];
        foreach ($this->fields[$className][$format] as $fieldName) {
            $fieldNames[] = $alias . '.' . $fieldName;
        }

        $nameDQL = reset($fieldNames);
        if (count($fieldNames) > 1) {
            $nameDQL = sprintf("CONCAT_WS(' ', %s)", implode(', ', $fieldNames));
        }

        return $this->addIdFallback($nameDQL, $alias, $className);
    }

    private function getSingleIdFieldName(string $className): ?string
    {
        $metadata = $this->getClassMetadata($className);
        if (!$metadata) {
            return null;
        }

        $identifierFieldNames = $metadata->getIdentifierFieldNames();
        if (count($identifierFieldNames) !== 1) {
            return null;
        }

        return reset($identifierFieldNames);
    }

    private function addIdFallback(string $nameDQL, string $alias, string $className): string
    {
        $idFieldName = $this->getSingleIdFieldName($className);
        if (null === $idFieldName) {
            return $nameDQL;
        }

        // use cast to avoid mixed collation errors
        return sprintf('COALESCE(CAST(%s AS string), CAST(%s AS string))', $nameDQL, $alias . '.' . $idFieldName);
    }

    private function getClassMetadata(string $className): ?ClassMetadata
    {
        $manager = $this->doctrine->getManagerForClass($className);
        if (null === $manager) {
            return null;
        }

        return $manager->getClassMetadata($className);
    }

    private function getFieldValue(object $entity, string $fieldName): mixed
    {
        $getterName = 'get' . $this->inflector->classify($fieldName);
        if (EntityPropertyInfo::methodExists($entity, $getterName)) {
            return $entity->{$getterName}();
        }

        return $entity->{$fieldName} ?? null;
    }

    private function getConstructedName(object $entity, array $fieldNames): ?string
    {
        $values = [];
        foreach ($fieldNames as $field) {
            $values[] = $this->getFieldValue($entity, $field);
        }

        $values = array_filter($values);

        return empty($values) ? null : implode(' ', $values);
    }
}
