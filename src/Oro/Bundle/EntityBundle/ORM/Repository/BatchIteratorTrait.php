<?php

namespace Oro\Bundle\EntityBundle\ORM\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\SingleObjectIterableResultDecorator;

trait BatchIteratorTrait
{
    /**
     * @return IterableResult
     */
    public function getBatchIterator()
    {
        /** @var EntityRepository $this */
        $qb = $this->createQueryBuilder('t');

        foreach ($this->getClassMetadata()->getIdentifierFieldNames() as $fieldName) {
            $qb->orderBy('t.' . $fieldName);
        }

        return new SingleObjectIterableResultDecorator($qb->getQuery()->iterate());
    }

    /**
     * @return ClassMetadata
     */
    abstract protected function getClassMetadata();
}
