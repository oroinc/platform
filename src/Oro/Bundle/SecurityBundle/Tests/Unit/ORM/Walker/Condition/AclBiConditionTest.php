<?php
namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker\Condition;

use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclBiCondition;

class AclBiConditionTest extends \PHPUnit_Framework_TestCase
{
    /** @var AclBiCondition */
    protected $obj;

    public function testConstruct()
    {
        $this->obj = new AclBiCondition('', '', '', '', '');
    }

    /**
     * @depends testConstruct
     *
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
