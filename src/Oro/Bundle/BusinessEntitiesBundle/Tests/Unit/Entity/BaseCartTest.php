<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Oro\Bundle\BusinessEntitiesBundle\Entity\BaseCart;

class BaseCartTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ID = 123;
    private const TEST_FLOAT = 123.123;

    private BaseCart $entity;

    protected function setUp(): void
    {
        $this->entity = new BaseCart();
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

        return [
            'id'        => ['id', self::TEST_ID, self::TEST_ID],
            'createdAt' => ['createdAt', $created, $created],
            'updatedAt' => ['updatedAt', $updated, $updated],
            'grandTotal' => ['grandTotal', self::TEST_FLOAT, self::TEST_FLOAT],
            'subTotal' => ['subTotal', self::TEST_FLOAT, self::TEST_FLOAT],
            'taxAmount' => ['taxAmount', self::TEST_FLOAT, self::TEST_FLOAT],
        ];
    }
}
