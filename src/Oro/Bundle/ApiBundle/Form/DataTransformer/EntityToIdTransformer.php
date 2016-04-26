<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityToIdTransformer implements DataTransformerInterface
{
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
    public function transform($value)
    {
        return null;
    }

    /**
     * {@inheritdoc}
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
        $manager = $this->doctrine->getManagerForClass($entityClass);
        if (null === $manager) {
            throw new TransformationFailedException(
                sprintf(
                    'The "%s" class must be a managed Doctrine entity.',
                    $entityClass
                )
            );
        }

        $entity = $manager->getRepository($entityClass)->find($value['id']);
        if (null === $entity) {
            throw new TransformationFailedException(
                sprintf(
                    'An "%s" entity with "%s" identifier does not exist.',
                    $entityClass,
                    $this->humanizeEntityId($value['id'])
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
            $elements = array_map(
                function ($key, $value) {
                    return sprintf('%s = %s', $key, $value);
                },
                array_keys($entityId),
                $entityId
            );

            return sprintf('array(%s)', implode(', ', $elements));
        }

        return (string)$entityId;
    }
}
