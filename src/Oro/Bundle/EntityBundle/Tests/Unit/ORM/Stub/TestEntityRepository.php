<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

class TestEntityRepository extends EntityRepository
{
    public function getEm(): EntityManager
    {
        return $this->_em;
    }

    public function getClass(): ClassMetadata
    {
        return $this->_class;
    }
}
