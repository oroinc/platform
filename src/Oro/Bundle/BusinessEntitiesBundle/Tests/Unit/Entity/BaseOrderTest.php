<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Oro\Bundle\BusinessEntitiesBundle\Entity\BaseOrder;

class BaseOrderTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING    = 'testString';
    const TEST_ID        = 123;
    const TEST_FLOAT     = 123.123;

    /** @var BaseOrder */
    protected $entity;

    public function setUp()
    {
        $this->entity = new BaseOrder();
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
            'createdAt' => ['createdAt', $created, $created],
            'updatedAt' => ['updatedAt', $updated, $updated],
            'customer' => ['customer', self::TEST_STRING, self::TEST_STRING],
            'paymentDetails' => ['paymentDetails', self::TEST_STRING, self::TEST_STRING],
            'paymentMethod' => ['paymentMethod', self::TEST_STRING, self::TEST_STRING],
            'discountAmount' => ['discountAmount', self::TEST_FLOAT, self::TEST_FLOAT],
            'discountPercent' => ['discountPercent', self::TEST_FLOAT, self::TEST_FLOAT],
            'shippingAmount' => ['shippingAmount', self::TEST_FLOAT, self::TEST_FLOAT],
            'shippingMethod' => ['shippingMethod', self::TEST_STRING, self::TEST_STRING],
            'currency' => ['currency', self::TEST_STRING, self::TEST_STRING],
            'status' => ['status', self::TEST_STRING, self::TEST_STRING],
            'subtotalAmount' => ['subtotalAmount', self::TEST_FLOAT, self::TEST_FLOAT],
            'taxAmount' => ['taxAmount', self::TEST_FLOAT, self::TEST_FLOAT],
            'totalAmount' => ['totalAmount', self::TEST_FLOAT, self::TEST_FLOAT],
            'items' => ['items', self::TEST_STRING, self::TEST_STRING],
        ];
    }
}
