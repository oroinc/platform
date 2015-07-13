<?php
namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker\Condition;

use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclNullCondition;

class AclNullConditionTest extends \PHPUnit_Framework_TestCase
{
    /** @var AclNullCondition */
    protected $obj;

    protected function setUp()
    {
        $this->obj = new AclNullCondition('aclEntries', 'record', true);
    }

    public function testConstruct()
    {
        $this->assertEquals('aclEntries', $this->obj->getEntityAlias());
        $this->assertEquals('record', $this->obj->getEntityField());
        $this->assertEquals(true, $this->obj->isNot());
    }

    public function testNot()
    {
        $this->obj->setNot(false);
        $this->assertEquals(false, $this->obj->isNot());
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
            'entityAlias' => array('entityAlias', 'entries', 'entries'),
            'entityField' => array('entityField', 'recordId', 'recordId'),
        );
    }
}
