<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;

class EntityToIdTransformer implements DataTransformerInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var AssociationMetadata */
    protected $metadata;

    /** @var IncludedEntityCollection|null */
    protected $includedEntities;

    /**
     * @param ManagerRegistry               $doctrine
     * @param AssociationMetadata           $metadata
     * @param IncludedEntityCollection|null $includedEntities
     */
    public function __construct(
        ManagerRegistry $doctrine,
        AssociationMetadata $metadata,
        IncludedEntityCollection $includedEntities = null
    ) {
        $this->doctrine = $doctrine;
        $this->metadata = $metadata;
        $this->includedEntities = $includedEntities;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function reverseTransform($value)
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }
        if (empty($value)) {
            return null;
        }

        if (empty($value['class'])) {
            throw new TransformationFailedException('Expected an array with "class" element.');
        }
        if (empty($value['id'])) {
            throw new TransformationFailedException('Expected an array with "id" element.');
        }

        $entityClass = $value['class'];
        $acceptableClassNames = $this->metadata->getAcceptableTargetClassNames();
        if (!empty($acceptableClassNames) && !in_array($entityClass, $acceptableClassNames, true)) {
            throw new TransformationFailedException(
                sprintf(
                    'The "%s" class is not acceptable. Acceptable classes: %s.',
                    $entityClass,
                    implode(',', $acceptableClassNames)
                )
            );
        }

        return $this->getEntity($entityClass, $value['id']);
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object
     */
    protected function getEntity($entityClass, $entityId)
    {
        $entity = $this->getIncludedEntity($entityClass, $entityId);
        if (null === $entity) {
            $manager = $this->doctrine->getManagerForClass($entityClass);
            if (null === $manager) {
                throw new TransformationFailedException(
                    sprintf(
                        'The "%s" class must be a managed Doctrine entity.',
                        $entityClass
                    )
                );
            }
            $entity = $manager->getRepository($entityClass)->find($entityId);
        }
        if (null === $entity) {
            throw new TransformationFailedException(
                sprintf(
                    'An "%s" entity with "%s" identifier does not exist.',
                    $entityClass,
                    $this->humanizeEntityId($entityId)
                )
            );
        }

        return $entity;
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object|null
     */
    protected function getIncludedEntity($entityClass, $entityId)
    {
        if (null === $this->includedEntities) {
            return null;
        }

        return $this->includedEntities->get($entityClass, $entityId);
    }

    /**
     * @param mixed $entityId
     *
     * @return string
     */
    protected function humanizeEntityId($entityId)
    {
        if (is_array($entityId)) {
            $elements = [];
            foreach ($entityId as $fieldName => $fieldValue) {
                $elements[] = sprintf('%s = %s', $fieldName, $fieldValue);
            }

            return sprintf('array(%s)', implode(', ', $elements));
        }

        return (string)$entityId;
    }
}
