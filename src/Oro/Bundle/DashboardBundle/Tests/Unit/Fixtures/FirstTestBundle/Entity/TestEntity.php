<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity;

/**
 * @Entity
 * @Table(name="test")
 */
class TestEntity
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="datetime")
     */
    public $createdAt;
}
