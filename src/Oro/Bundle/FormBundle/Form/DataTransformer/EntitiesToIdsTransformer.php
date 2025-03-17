<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between array of entities and array of IDs.
 */
class EntitiesToIdsTransformer extends EntityToIdTransformer
{
    #[\Override]
    public function transform($value)
    {
        if (null === $value || [] === $value) {
            return [];
        }

        if (!\is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array');
        }

        $result = [];
        foreach ($value as $entity) {
            $id = $this->getPropertyAccessor()->getValue($entity, $this->getPropertyPath());
            $result[] = $id;
        }

        return $result;
    }

    #[\Override]
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        if (!\is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array');
        }

        return $this->loadEntitiesByIds($value);
    }

    /**
     * @throws UnexpectedTypeException if query builder callback returns invalid type
     * @throws TransformationFailedException if values not matched given $ids
     */
    protected function loadEntitiesByIds(array $ids): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->doctrine->getRepository($this->className);
        if ($this->queryBuilderCallback) {
            /** @var QueryBuilder $qb */
            $qb = \call_user_func($this->queryBuilderCallback, $repository, $ids);
            if (!$qb instanceof QueryBuilder) {
                throw new UnexpectedTypeException($qb, QueryBuilder::class);
            }
        } else {
            $qb = $repository->createQueryBuilder('e');
            $qb->where(\sprintf('e.%s IN (:ids)', $this->getProperty()))
                ->setParameter('ids', $ids);
        }

        $result = $qb->getQuery()->execute();

        if (\count($result) !== \count($ids)) {
            throw new TransformationFailedException('Could not find all entities for the given IDs');
        }

        return $result;
    }
}
