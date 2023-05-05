<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Oro\Bundle\BusinessEntitiesBundle\Entity\BasePersonGroup;

class BasePersonGroupTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_NAME = 'testGroupName';
    private const TEST_ID = 123;

    private BasePersonGroup $entity;

    protected function setUp(): void
    {
        $this->entity = new BasePersonGroup();
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
        return [
            'id'   => ['id', self::TEST_ID, self::TEST_ID],
            'name' => ['name', self::TEST_NAME, self::TEST_NAME],
        ];
    }
}
