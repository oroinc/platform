<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Oro\Bundle\BusinessEntitiesBundle\Entity\BaseCart;
use Oro\Bundle\BusinessEntitiesBundle\Entity\BaseCartItem;

class BaseCartItemTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_STRING = 'testString';
    private const TEST_ID = 123;
    private const TEST_FLOAT = 123.123;

    private BaseCartItem $entity;

    protected function setUp(): void
    {
        $this->entity = new BaseCartItem();
    }

    /**
     * @dataProvider getSetDataProvider
     */
    public function testSetGet(string $property, mixed $value = null, mixed $expected = null)
    {
        if ($value !== null) {
            call_user_func([$this->entity, 'set' . ucfirst($property)], $value);
        }

        $this->assertEquals($expected, call_user_func_array([$this->entity, 'get' . ucfirst($property)], []));
    }

    public function getSetDataProvider(): array
    {
        $created = new \DateTime('now');
        $updated = new \DateTime('now');
        $cartMock = $this->createMock(BaseCart::class);

        return [
            'id'             => ['id', self::TEST_ID, self::TEST_ID],
            'name'           => ['name', self::TEST_STRING, self::TEST_STRING],
            'cart'           => ['cart', $cartMock, $cartMock],
            'price'          => ['price', self::TEST_FLOAT, self::TEST_FLOAT],
            'qty'            => ['qty', self::TEST_FLOAT, self::TEST_FLOAT],
            'sku'            => ['sku', self::TEST_STRING, self::TEST_STRING],
            'weight'         => ['weight', self::TEST_FLOAT, self::TEST_FLOAT],
            'discountAmount' => ['discountAmount', self::TEST_FLOAT, self::TEST_FLOAT],
            'taxPercent'     => ['taxPercent', self::TEST_FLOAT, self::TEST_FLOAT],
            'createdAt'      => ['createdAt', $created, $created],
            'updatedAt'      => ['updatedAt', $updated, $updated],
        ];
    }
}
