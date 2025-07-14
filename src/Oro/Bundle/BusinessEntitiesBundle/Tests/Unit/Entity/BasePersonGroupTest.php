<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Oro\Bundle\BusinessEntitiesBundle\Entity\BasePersonGroup;
use PHPUnit\Framework\TestCase;

class BasePersonGroupTest extends TestCase
{
    private const TEST_NAME = 'testGroupName';
    private const TEST_ID = 123;

    private BasePersonGroup $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new BasePersonGroup();
    }

    /**
     * @dataProvider getSetDataProvider
     */
    public function testSetGet(string $property, mixed $value = null, mixed $expected = null): void
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
