<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;

class EntityNameProvider implements EntityNameProviderInterface
{
    /** @var ObjectMapper $objectMapper */
    protected $objectMapper;

    /**
     * @param ObjectMapper $objectMapper
     */
    public function __construct(ObjectMapper $objectMapper)
    {
        $this->objectMapper = $objectMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if ($format !== self::FULL) {
            return false;
        }

        $entityClass = ClassUtils::getClass($entity);
        $fields = $this->objectMapper->getEntityMapParameter($entityClass, 'title_fields', []);

        $fieldValues = [];
        foreach ($fields as $field) {
            $fieldValues[] = $this->objectMapper->getFieldValue($entity, $field);
        }

        $fieldValues = array_filter($fieldValues);
        if (count($fieldValues) > 0) {
            return implode(' ', $fieldValues);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        if ($format !== self::FULL) {
            return;
        }

        $fields = $this->objectMapper->getEntityMapParameter($className, 'title_fields', []);
        if (0 === count($fields)) {
            return false;
        }

        // prepend table alias
        $fields = array_map(function ($fieldName) use ($alias) {
            return $alias . '.' . $fieldName;
        }, $fields);

        if (1 === count($fields)) {
            return reset($fields);
        }

        // more than one field name
        return sprintf("CONCAT_WS(' ', %s)", implode(', ', $fields));
    }
}
