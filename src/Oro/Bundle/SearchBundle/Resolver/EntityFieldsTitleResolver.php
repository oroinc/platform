<?php

namespace Oro\Bundle\SearchBundle\Resolver;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;

class EntityFieldsTitleResolver implements EntityTitleResolverInterface
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
    public function resolve($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        $fields = $this->objectMapper->getEntityMapParameter($entityClass, 'title_fields', []);

        $fieldValues = [];
        foreach ($fields as $field) {
            $fieldValues[] = $this->objectMapper->getFieldValue($entity, $field);
        }

        return implode(' ', array_filter($fieldValues));
    }
}
