<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Oro\Bundle\BusinessEntitiesBundle\Entity\BaseCustomerEntity;

class BaseCustomerEntityTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING = 'testString';

    /** @var BaseCustomerEntity */
    protected $entity;

    public function setUp()
    {
        $this->entity = new BaseCustomerEntity();
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
        $birthday = new \DateTime('now');

        return [
            'id'          => ['id'],
            'namePrefix'  => ['namePrefix', self::TEST_STRING . 'prefix', self::TEST_STRING . 'prefix'],
            'firstName'   => ['firstName', self::TEST_STRING . 'first', self::TEST_STRING . 'first'],
            'middleName'  => ['middleName', self::TEST_STRING . 'middle', self::TEST_STRING . 'middle'],
            'lastName'    => ['lastName', self::TEST_STRING . 'last', self::TEST_STRING . 'last'],
            'nameSuffix'  => ['nameSuffix', self::TEST_STRING . 'suffix', self::TEST_STRING . 'suffix'],
            'gender'      => ['gender', 'male', 'male'],
            'birthday'    => ['birthday', $birthday, $birthday],
            'email'       => ['email', self::TEST_STRING . 'email', self::TEST_STRING . 'email'],
            'phone'       => ['phone', self::TEST_STRING . 'phone', self::TEST_STRING . 'phone'],
            'vat'         => ['vat', self::TEST_STRING . 'vat', self::TEST_STRING . 'vat'],
            'description' => ['description', self::TEST_STRING . 'description', self::TEST_STRING . 'description'],

        ];
    }
}
