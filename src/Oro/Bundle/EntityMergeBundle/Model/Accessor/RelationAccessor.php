<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Accessor;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class RelationAccessor implements AccessorInterface
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return PropertyAccessor
     */
    public function getName()
    {
        return 'relation';
    }

    /**
     * Checks if this class supports accessing entity
     *
     * @param string        $entity
     * @param FieldMetadata $metadata
     * @return string
     */
    public function supports($entity, FieldMetadata $metadata)
    {
        return !$metadata->getDoctrineMetadata()->isMappedBySourceEntity();
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        $fieldName = $metadata->getFieldName();
        $className = $metadata->getDoctrineMetadata()->get('sourceEntity');

        return $this
            ->entityManager
            ->getRepository($className)
            ->findBy([$fieldName => $entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        foreach ($value as $relatedEntity) {
            if ($metadata->has('setter')) {
                $setter = $metadata->get('setter');
                $relatedEntity->$setter($entity);

                continue;
            }

            $this
                ->getPropertyAccessor()
                ->setValue(
                    $relatedEntity,
                    $this->getPropertyPath($metadata),
                    $entity
                );
        }
    }

    /**
     * @param FieldMetadata $metadata
     * @return string
     */
    protected function getPropertyPath(FieldMetadata $metadata)
    {
        return $metadata->has('property_path') ?
            $metadata->get('property_path') : $metadata->getFieldName();
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }
        return $this->propertyAccessor;
    }
}
