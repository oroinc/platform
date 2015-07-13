<?php
namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker\Condition;

use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclBiCondition;
use Oro\Bundle\SecurityBundle\ORM\Walker\Statement\AclJoinClause;
use Oro\Bundle\SecurityBundle\ORM\Walker\Statement\AclJoinStatement;

class AclJoinStatementTest extends \PHPUnit_Framework_TestCase
{
    /** @var AclJoinStatement */
    protected $obj;

    /** @var AclJoinClause */
    protected $joinClause;

    protected function setUp()
    {
        $this->joinClause = new AclJoinClause('Oro\Bundle\SecurityBundle\Entity\AclEntry', 'entries', 'c1', 'entries1');
        $this->obj = new AclJoinStatement($this->joinClause, [], []);
    }

    public function testConstruct()
    {
        $this->assertEquals($this->joinClause, $this->obj->getJoinClause());
        $this->assertEmpty($this->obj->getWhereConditions());
        $this->assertEmpty($this->obj->getJoinConditions());
        $this->assertEquals(AclJoinStatement::ACL_SHARE_STATEMENT, $this->obj->getMethod());
    }

    /**
     * @dataProvider flatPropertiesDataProvider
     */
    public function testGetSet($property, $value, $expected)
    {
        call_user_func_array([$this->obj, 'set' . ucfirst($property)], [$value]);
        $this->assertEquals($expected, call_user_func_array([$this->obj, 'get' . ucfirst($property)], []));
    }

    public function flatPropertiesDataProvider()
    {
        $joinClause = new AclJoinClause('Oro\Bundle\SecurityBundle\Entity\AclEntry', 'entries', 'c1', 'entries1');
        $biCondition = new AclBiCondition('aclEntries', 'record', 'c1', 'id1');
        $aclCondition = new AclCondition('testClass', 'id', array(1));
        $whereConditions = [$aclCondition];
        $joinConditions = [$biCondition];

        return [
            'joinClause' => ['joinClause', $joinClause, $joinClause],
            'whereConditions' => ['whereConditions', $whereConditions, $whereConditions],
            'joinConditions' => ['joinConditions', $joinConditions, $joinConditions],
            'method' => ['method', 'bu_voter', 'bu_voter'],
        ];
    }
}
