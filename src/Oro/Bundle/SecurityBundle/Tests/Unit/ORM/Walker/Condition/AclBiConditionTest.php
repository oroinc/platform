<?php
namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker\Condition;

use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclBiCondition;

class AclBiConditionTest extends \PHPUnit_Framework_TestCase
{
    /** @var AclBiCondition */
    protected $obj;

    protected function setUp()
    {
        $this->obj = new AclBiCondition('aclEntries', 'record', 'c1', 'id1');
    }

    public function testConstruct()
    {
        $this->assertEquals('aclEntries', $this->obj->getEntityAliasLeft());
        $this->assertEquals('record', $this->obj->getEntityFieldLeft());
        $this->assertEquals('c1', $this->obj->getEntityAliasRight());
        $this->assertEquals('id1', $this->obj->getEntityFieldRight());
    }

    /**
     * @dataProvider flatPropertiesDataProvider
     */
    public function testGetSet($property, $value, $expected)
    {
        call_user_func_array(array($this->obj, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($expected, call_user_func_array(array($this->obj, 'get' . ucfirst($property)), array()));
    }

    public function flatPropertiesDataProvider()
    {
        return array(
            'entityAliasLeft' => array('entityAliasLeft', 'entries', 'entries'),
            'entityFieldLeft' => array('entityFieldLeft', 'recordId', 'recordId'),
            'entityAliasRight' => array('entityAliasRight', 'c', 'c'),
            'entityFieldRight' => array('entityFieldRight', 'id', 'id'),
        );
    }
}
