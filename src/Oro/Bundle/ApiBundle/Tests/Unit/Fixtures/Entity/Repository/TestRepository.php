<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class TestRepository extends EntityRepository
{
    /**
     * Data that can be returned with find method.
     *
     * @var array
     *   - key = entity id or imploded ids in case if entity has combined primary key
     *   - value = object
     */
    public $data;

    /**
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        if (is_array($id)) {
            $id = implode('|', $id);
        }

        return isset($this->data[$id]) ? $this->data[$id] : null;
    }
}
