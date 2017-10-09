<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\ORMException;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

abstract class AbstractEntityAssociationTransformer implements DataTransformerInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EntityLoader */
    protected $entityLoader;

    /** @var AssociationMetadata */
    protected $metadata;

    /**
     * @param ManagerRegistry     $doctrine
     * @param EntityLoader        $entityLoader
     * @param AssociationMetadata $metadata
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EntityLoader $entityLoader,
        AssociationMetadata $metadata
    ) {
        $this->doctrine = $doctrine;
        $this->entityLoader = $entityLoader;
        $this->metadata = $metadata;
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
        if (empty($acceptableClassNames)) {
            if (!$this->metadata->isEmptyAcceptableTargetsAllowed()) {
                throw new TransformationFailedException('There are no acceptable classes.');
            }
        } elseif (!in_array($entityClass, $acceptableClassNames, true)) {
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
    abstract protected function getEntity($entityClass, $entityId);

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object
     */
    protected function loadEntity($entityClass, $entityId)
    {
        $manager = $this->doctrine->getManagerForClass($entityClass);
        if (null === $manager) {
            throw new TransformationFailedException(
                sprintf(
                    'The "%s" class must be a managed Doctrine entity.',
                    $entityClass
                )
            );
        }
        $entity = null;
        try {
            $entity = $this->entityLoader->findEntity($entityClass, $entityId, $this->metadata->getTargetMetadata());
        } catch (ORMException $e) {
            throw new TransformationFailedException(
                sprintf(
                    'An "%s" entity with "%s" identifier cannot be loaded.',
                    $entityClass,
                    $this->humanizeEntityId($entityId)
                ),
                0,
                $e
            );
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
