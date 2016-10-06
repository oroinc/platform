<?php

namespace Oro\Bundle\EntityBundle\Provider;

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
        if ($format === self::SHORT) {
            $fieldName = $this->getFieldName(ClassUtils::getClass($entity));
            if ($fieldName) {
                return $this->getFieldValue($entity, $fieldName);
            }
        }

        if ($format === self::FULL) {
            $fieldNames = $this->getFieldNames(ClassUtils::getClass($entity));
            if (count($fieldNames) > 0) {
                return implode(' ', $fieldNames);
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

            // append table alias
            $fieldNames = array_map(function ($fieldName) use ($alias) {
                return $alias . '.' . $fieldName;
            }, $fieldNames);

            if (1 === count($fieldNames)) {
                return reset($fieldNames);
            }

            // more than one field name
            return sprintf("CONCAT_WS(' ', %s)", implode(', ', $fieldNames));
        }

        return false;
    }

    /**
     * @param string $className
     *
     * @return string|null
     */
    protected function getFieldName($className)
    {
        $manager = $this->doctrine->getManagerForClass($className);
        if (null === $manager) {
            return null;
        }

        $metadata = $manager->getClassMetadata($className);
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
        return $entity->{'get' . Inflector::camelize($fieldName)}();
    }

    /**
     * Return string field names of className
     * @param  string $className
     * @return array
     */
    protected function getFieldNames($className)
    {
        if (null === $manager = $this->doctrine->getManagerForClass($className)) {
            return [];
        }

        $metadata = $manager->getClassMetadata($className);
        $fieldNames = $metadata->getFieldNames();

        return array_filter($fieldNames, function ($fieldName) use ($metadata) {
            return 'string' === $metadata->getTypeOfField($fieldName);
        });
    }
}
