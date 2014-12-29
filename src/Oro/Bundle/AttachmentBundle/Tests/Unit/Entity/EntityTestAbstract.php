<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

abstract class EntityTestAbstract extends \PHPUnit_Framework_TestCase
{
    protected $entity;

    public function tearDown()
    {
        unset($this->entity);
    }

    /**
     * @dataProvider  getSetDataProvider
     *
     * @param string $property
     * @param mixed $value
     * @param mixed $expected
     */
    public function testSetGet($property, $value = null, $expected = null)
    {
        if ($value !== null) {
            call_user_func_array(array($this->entity, 'set' . ucfirst($property)), [$value]);
        }

        $this->assertEquals($expected, call_user_func_array(array($this->entity, 'get' . ucfirst($property)), []));
    }

    /**
     * @return array
     */
    abstract public function getSetDataProvider();
}
