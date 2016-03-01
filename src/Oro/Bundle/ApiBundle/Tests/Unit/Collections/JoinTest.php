<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collections;

use Oro\Bundle\ApiBundle\Collection\Join;

class JoinTest extends \PHPUnit_Framework_TestCase
{
    /** @var Join */
    protected $joinType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->joinType = new Join(Join::LEFT_JOIN, Join::WITH);
    }

    public function testSetJoinType()
    {
        $this->assertEquals(Join::LEFT_JOIN, $this->joinType->getJoinType());
        $this->joinType->setJoinType(Join::INNER_JOIN);
        $this->assertSame(Join::INNER_JOIN, $this->joinType->getJoinType());
    }

    public function testSetAlias()
    {
        $this->assertNull($this->joinType->getAlias());
        $this->joinType->setAlias('alias');
        $this->assertSame('alias', $this->joinType->getAlias());
    }
}
