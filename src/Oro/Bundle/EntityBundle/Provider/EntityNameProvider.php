<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Util\Inflector;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Generic resolver for entity names(titles):
 * - Short format returns name based on guesses using first existing field from the list $fieldGuesses or false
 * if no appropriate field is found
 * - Full format returns concatenation of all string fields or false if no string fields are found.
 * When existing fields have empty values, identifier (if exists and is single) will be used as title.
 */
class EntityNameProvider implements EntityNameProviderInterface
{
    /** @var string[] */
    protected $fieldGuesses = ['firstName', 'name', 'title', 'subject'];

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ConfigProvider  */
    protected $configProvider;

    /**
     * @param ManagerRegistry $doctrine
     * @param ConfigProvider  $configProvider
     */
    public function __construct(ManagerRegistry $doctrine, ConfigProvider $configProvider)
    {
        $this->doctrine = $doctrine;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!in_array($format, [self::SHORT, self::FULL])) {
            // unsupported format
            return false;
        }

        $className = ClassUtils::getClass($entity);

        $fieldNames = self::FULL === $format
            ? $this->getFieldNames($className)
            : (array) $this->guessFieldName($className);

        if (empty($fieldNames)) {
            // no suitable fields
            return false;
        }

        if ($name = $this->getConstructedName($entity, $fieldNames)) {
            return $name;
        }

        // field value is empty, try with id
        if ($idFiledName = $this->getSingleIdFieldName($className)) {
            return $this->getFieldValue($entity, $idFiledName);
        }

        // no identifier column
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if (!in_array($format, [self::SHORT, self::FULL])) {
            // unsupported format
            return false;
        }

        if ($format === self::SHORT) {
            $guessFieldName = $this->guessFieldName($className);
            if (!$guessFieldName) {
                return false;
            }

            $nameDQL = $alias . '.' . $guessFieldName;

            return $this->addIdFallback($nameDQL, $alias, $className);
        }

        $fieldNames = $this->getFieldNames($className);
        if (0 === count($fieldNames)) {
            return false;
        }

        // prepend table alias
        $fieldNames = array_map(
            function ($fieldName) use ($alias) {
                return $alias . '.' . $fieldName;
            },
            $fieldNames
        );


        $nameDQL = reset($fieldNames);

        if (count($fieldNames) > 1) {
            $nameDQL = sprintf("CONCAT_WS(' ', %s)", implode(', ', $fieldNames));
        }

        return $this->addIdFallback($nameDQL, $alias, $className);
    }

    /**
     * Return single class Identifier Field Name or null if there a multiple or none
     *
     * @param $className
     *
     * @return string|null
     */
    protected function getSingleIdFieldName($className)
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
     * Adds the identifier column to the DQL as name fallback (if identifier exists and is only one)
     *
     * @param $nameDQL
     * @param $alias
     * @param $className
     *
     * @return string
     */
    protected function addIdFallback($nameDQL, $alias, $className)
    {
        $idFieldName = $this->getSingleIdFieldName($className);

        if (null === $idFieldName) {
            return $nameDQL;
        }

        // use cast to avoid mixed collation errors
        return sprintf('COALESCE(CAST(%s AS string), CAST(%s AS string))', $nameDQL, $alias . '.' . $idFieldName);
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
     * Return first string field from the fieldGuesses or null
     *
     * @param string $className
     *
     * @return string|null
     */
    protected function guessFieldName($className)
    {
        $metadata = $this->getClassMetadata($className);

        if (!$metadata) {
            return null;
        }

        foreach ($this->fieldGuesses as $fieldName) {
            if ($this->isFieldSupported($metadata, $fieldName)) {
                return $fieldName;
            }
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
            return $entity->{$getterName}();
        }

        if (isset($entity->{$fieldName})) {
            return $entity->{$fieldName};
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
                return $this->isFieldSupported($metadata, $fieldName);
            }
        );

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

    /**
     * Returns whether field is available to be used as entity name
     *
     * @param ClassMetadata $metadata
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isFieldSupported(ClassMetadata $metadata, $fieldName)
    {
        $isFieldSupported = $metadata->hasField($fieldName) && $metadata->getTypeOfField($fieldName) === 'string';

        if ($isFieldSupported && $this->configProvider->hasConfig($metadata->getName(), $fieldName)) {
            $fieldConfig = $this->configProvider->getConfig($metadata->getName(), $fieldName);
            $isFieldSupported = ExtendHelper::isFieldAccessible($fieldConfig);
        }

        return $isFieldSupported;
    }
}
