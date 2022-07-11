<?php

namespace Oro\Bundle\AddressBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorInterface;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorTrait;

/**
 * The repository for AddressType entity.
 */
class AddressTypeRepository extends EntityRepository implements BatchIteratorInterface
{
    use BatchIteratorTrait;
}
