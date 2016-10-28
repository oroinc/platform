<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;

class EntityNameProvider implements EntityNameProviderInterface
{
    /** @var string[] */
    protected $fieldGuesses = ['firstName', 'name', 'title', 'subject'];

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        $className = ClassUtils::getClass($entity);
        if ($format === self::SHORT) {
            return $this->getConstructedName($entity, [$this->getFieldName($className)]);
        }

        if ($format === self::FULL) {
            if ($name = $this->getConstructedName($entity, $this->getFieldNames($className))) {
                return $name;
            }

            if ($idFiledName = $this->getIdFieldName($className)) {
                return $idFiledName;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($format === self::SHORT) {
            $fieldName = $this->getFieldName($className);
            if ($fieldName) {
                return $alias . '.' . $fieldName;
            }
        }

        if ($format === self::FULL) {
            $fieldNames = $this->getFieldNames($className);
            if (0 === count($fieldNames)) {
                return false;
            }

            // prepend table alias
            $fieldNames = array_map(function ($fieldName) use ($alias) {
                return $alias . '.' . $fieldName;
            }, $fieldNames);

            $idColumnName = sprintf('%s.%s', $alias, $this->getIdFieldName($className));

            if (count($fieldNames) === 0) {
                return $idColumnName;
            }

            $nameDQL = reset($fieldNames);

            if (count($fieldNames) > 1) {
                $nameDQL = sprintf("CONCAT_WS(' ', %s)", implode(', ', $fieldNames));
            }

            return sprintf('COALESCE(%s, %s)', $nameDQL, $idColumnName);
        }

        return false;
    }

    /**
     * Return single class Identifier Field Name or null if there a multiple or none
     *
     * @param $className
     *
     * @return string|null
     */
    protected function getIdFieldName($className)
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

    /**
     * Return metadata of className
     *
     * @param string $className
     *
     * @return ClassMetadata|null
     */
    protected function getClassMetadata($className)
    {
        $manager = $this->doctrine->getManagerForClass($className);
        if (null === $manager) {
            return null;
        }

        return $manager->getClassMetadata($className);
    }

    /**
     * @param string $className
     *
     * @return string|null
     */
    protected function getFieldName($className)
    {
        $metadata = $this->getClassMetadata($className);

        if (!$metadata) {
            return null;
        }

        foreach ($this->fieldGuesses as $fieldName) {
            if ($metadata->hasField($fieldName) && $metadata->getTypeOfField($fieldName) === 'string') {
                return $fieldName;
            }
        }

        $identifierFieldNames = $metadata->getIdentifierFieldNames();
        if (count($identifierFieldNames) === 1) {
            return reset($identifierFieldNames);
        }

        return null;
    }

    /**
     * @param object $entity
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getFieldValue($entity, $fieldName)
    {
        $getterName = 'get' . Inflector::classify($fieldName);

        if (method_exists($entity, $getterName)) {
            return $entity->$getterName();
        }

        if (property_exists($entity, $fieldName)) {
            return $entity->$fieldName;
        }

        return null;
    }

    /**
     * Return string field names of className
     * Return first string field match from fieldGuesses or all string fields
     *
     * @param  string $className
     *
     * @return array
     */
    protected function getFieldNames($className)
    {
        $metadata = $this->getClassMetadata($className);

        if (!$metadata) {
            return [];
        }

        $fieldNames = array_filter(
            (array) $metadata->getFieldNames(),
            function ($fieldName) use ($metadata) {
                return 'string' === $metadata->getTypeOfField($fieldName);
            }
        );

        $guessFiledNames = array_intersect($fieldNames, $this->fieldGuesses);

        if (!empty($guessFiledNames)) {
            return (array) reset($guessFiledNames);
        }

        return $fieldNames;
    }

    /**
     * Constructs and returns a name from the values of the fieldNames
     *
     * @param $entity
     * @param $fieldNames
     *
     * @return string|bool Constructed Name or FALSE if fails
     */
    protected function getConstructedName($entity, $fieldNames)
    {
        $values = [];
        foreach ($fieldNames as $field) {
            $values[] = $this->getFieldValue($entity, $field);
        }

        $values = array_filter($values);

        return empty($values) ? false : implode(' ', $values);
    }
}
