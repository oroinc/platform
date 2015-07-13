<?php
namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker\Condition;

use Oro\Bundle\SecurityBundle\ORM\Walker\Statement\AclJoinClause;
use Oro\Bundle\SecurityBundle\ORM\Walker\Statement\AclJoinStatement;
use Oro\Bundle\SecurityBundle\ORM\Walker\Statement\AclJoinStorage;

class AclJoinStorageTest extends \PHPUnit_Framework_TestCase
{
    /** @var AclJoinStatement */
    protected $joinStatement;

    /** @var AclJoinStorage */
    protected $obj;

    protected function setUp()
    {
        $joinClause = new AclJoinClause('Oro\Bundle\SecurityBundle\Entity\AclEntry', 'entries', 'c1', 'entries1');
        $this->joinStatement = new AclJoinStatement($joinClause, [], []);
        $this->obj = new AclJoinStorage([$this->joinStatement]);
    }

    public function testConstruct()
    {
        $this->assertEquals([$this->joinStatement], $this->obj->getJoinStatements());
    }

    public function testIsEmpty()
    {
        $this->assertFalse($this->obj->isEmpty());
        $this->obj->setJoinStatements([]);
        $this->assertTrue($this->obj->isEmpty());
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
        return [
            'joinStatements' => ['joinStatements', [], []],
        ];
    }
}
