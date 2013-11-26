<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Oro\Bundle\BusinessEntitiesBundle\Entity\BasePersonGroup;

class BasePersonGroupTest extends \PHPUnit_Framework_TestCase
{
    const TEST_NAME = 'testGroupName';
    const TEST_ID   = 123;

    /** @var BasePersonGroup */
    protected $entity;

    public function setUp()
    {
        $this->entity = new BasePersonGroup();
    }

    public function tearDown()
    {
        unset($this->entity);
    }

    /**
     * @dataProvider  getSetDataProvider
     *
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expected
     */
    public function testSetGet($property, $value = null, $expected = null)
    {
        if ($value !== null) {
            call_user_func_array(array($this->entity, 'set' . ucfirst($property)), array($value));
        }

        $this->assertEquals($expected, call_user_func_array(array($this->entity, 'get' . ucfirst($property)), array()));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        return [
            'id'   => ['id', self::TEST_ID, self::TEST_ID],
            'name' => ['name', self::TEST_NAME, self::TEST_NAME],
        ];
    }
}
