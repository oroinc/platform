<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Oro\Bundle\BusinessEntitiesBundle\Entity\BaseProduct;

class BaseProductTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_STRING = 'testString';
    private const TEST_ID = 123;
    private const TEST_FLOAT = 123.123;

    private BaseProduct $entity;

    protected function setUp(): void
    {
        $this->entity = new BaseProduct();
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
            'name'      => ['name', self::TEST_STRING . 'name', self::TEST_STRING . 'name'],
            'sku'       => ['sku', self::TEST_STRING . 'sku', self::TEST_STRING . 'sku'],
            'type'      => ['type', self::TEST_STRING . 'type', self::TEST_STRING . 'type'],
            'cost'      => ['cost', self::TEST_FLOAT, self::TEST_FLOAT],
            'price'     => ['price', self::TEST_FLOAT, self::TEST_FLOAT],
            'createdAt' => ['createdAt', $created, $created],
            'updatedAt' => ['updatedAt', $updated, $updated],
        ];
    }
}
