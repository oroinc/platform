<?php

namespace Oro\Bundle\SegmentBundle\Autocomplete;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Autocomplete handler for Segment entities.
 * Supports filtering by entity class via "entity_class" request parameter.
 */
class SegmentSearchHandler implements SearchHandlerInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[\Override]
    public function search($query, $page, $perPage, $searchById = false): array
    {
        $entityClass = $this->requestStack->getCurrentRequest()?->query->get('entity_class');

        $qb = $this->doctrine->getRepository(Segment::class)->createQueryBuilder('s');
        $qb->orderBy('s.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage + 1);

        if ($searchById) {
            $qb->where('s.id = :id')->setParameter('id', (int)$query);
        } elseif ($query !== '' && $query !== null) {
            $qb->where('s.name LIKE :query')->setParameter('query', '%' . $query . '%');
        }

        if ($entityClass) {
            $qb->andWhere('s.entity = :entity')->setParameter('entity', $entityClass);
        }

        $results = $qb->getQuery()->getResult();
        $hasMore = \count($results) > $perPage;
        if ($hasMore) {
            array_pop($results);
        }

        return [
            'results' => array_map($this->convertItem(...), $results),
            'more'    => $hasMore,
        ];
    }

    #[\Override]
    public function getEntityName(): string
    {
        return Segment::class;
    }

    #[\Override]
    public function getProperties(): array
    {
        return ['id', 'name'];
    }

    #[\Override]
    public function convertItem($item): array
    {
        return [
            'id'   => $item->getId(),
            'name' => $item->getName(),
        ];
    }
}
