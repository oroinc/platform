<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Oro\Bundle\BusinessEntitiesBundle\Entity\BaseCartItem;

class BaseCartItemTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING    = 'testString';
    const TEST_ID        = 123;
    const TEST_FLOAT     = 123.123;

    /** @var BaseCartItem */
    protected $entity;

    public function setUp()
    {
        $this->entity = new BaseCartItem();
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
        $created  = new \DateTime('now');
        $updated  = new \DateTime('now');

        return [
            'id'        => ['id', self::TEST_ID, self::TEST_ID],
            'name' => ['name', self::TEST_STRING, self::TEST_STRING],
            'cart' => ['cart', self::TEST_STRING, self::TEST_STRING],
            'price' => ['price', self::TEST_FLOAT, self::TEST_FLOAT],
            'qty' => ['qty', self::TEST_FLOAT, self::TEST_FLOAT],
            'sku' => ['sku', self::TEST_STRING, self::TEST_STRING],
            'weight' => ['weight', self::TEST_FLOAT, self::TEST_FLOAT],
            'discountAmount' => ['discountAmount', self::TEST_FLOAT, self::TEST_FLOAT],
            'taxPercent' => ['taxPercent', self::TEST_FLOAT, self::TEST_FLOAT],
            'createdAt' => ['createdAt', $created, $created],
            'updatedAt' => ['updatedAt', $updated, $updated],
        ];
    }
}
