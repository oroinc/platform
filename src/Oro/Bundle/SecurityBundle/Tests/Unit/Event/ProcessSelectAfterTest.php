<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Event;

use Oro\Bundle\SecurityBundle\Event\ProcessSelectAfter;

class ProcessSelectAfterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $selectStatement    = $this->getMockBuilder('Doctrine\ORM\Query\AST\SelectStatement')
            ->disableOriginalConstructor()->getMock();
        $whereCondition     = ['someWhere' => 'condition'];
        $joinCondition      = ['someJoin' => 'condition'];
        $processSelectAfter = new ProcessSelectAfter($selectStatement, $whereCondition, $joinCondition);

        $this->assertEquals($processSelectAfter->getJoinConditions(), $joinCondition);
        $this->assertEquals($processSelectAfter->getWhereConditions(), $whereCondition);
        $this->assertEquals($processSelectAfter->getSelect(), $selectStatement);
    }
}
