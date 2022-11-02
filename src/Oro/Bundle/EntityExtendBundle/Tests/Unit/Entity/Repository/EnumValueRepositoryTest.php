<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private const ENUM_VALUE_CLASS_NAME = TestEnumValue::class;

    /** @var EnumValueRepository */
    private $repo;

    protected function setUp(): void
    {
        $this->repo = new EnumValueRepository(
            $this->createMock(EntityManagerInterface::class),
            new ClassMetadata(self::ENUM_VALUE_CLASS_NAME)
        );
    }

    public function testCreateEnumValueWithNullName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$name must not be empty.');

        $this->repo->createEnumValue(null, 1, false);
    }

    public function testCreateEnumValueWithEmptyName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$name must not be empty.');

        $this->repo->createEnumValue('', 1, false);
    }

    public function testCreateEnumValueWithTooLongId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '$id length must be less or equal 32 characters. id: 123456789012345678901234567890123.'
        );

        $this->repo->createEnumValue(
            'Test Value 1',
            1,
            false,
            '123456789012345678901234567890123'
        );
    }

    public function testCreateEnumValue()
    {
        $result = $this->repo->createEnumValue('Test Value 1', 1, false, 'val1');

        $this->assertInstanceOf(self::ENUM_VALUE_CLASS_NAME, $result);
        $this->assertEquals('val1', $result->getId());
        $this->assertEquals('Test Value 1', $result->getName());
        $this->assertEquals(1, $result->getPriority());
        $this->assertFalse($result->isDefault());
    }

    public function testCreateEnumValueWithoutId()
    {
        $result = $this->repo->createEnumValue('Test Value 1', 1, true);

        $this->assertInstanceOf(self::ENUM_VALUE_CLASS_NAME, $result);
        $this->assertEquals(ExtendHelper::buildEnumValueId('Test Value 1'), $result->getId());
        $this->assertEquals('Test Value 1', $result->getName());
        $this->assertEquals(1, $result->getPriority());
        $this->assertTrue($result->isDefault());
    }

    public function testCreateEnumValueWithZeroAsKey()
    {
        $result = $this->repo->createEnumValue('Test Value 1', 1, true, '0');

        $this->assertInstanceOf(self::ENUM_VALUE_CLASS_NAME, $result);
        $this->assertSame('0', $result->getId());
        $this->assertEquals('Test Value 1', $result->getName());
        $this->assertEquals(1, $result->getPriority());
        $this->assertTrue($result->isDefault());
    }

    public function testCreateEnumValueWithEmptyString()
    {
        $result = $this->repo->createEnumValue('Test Value 1', 1, true, '');

        $this->assertInstanceOf(self::ENUM_VALUE_CLASS_NAME, $result);
        $this->assertEquals('test_value_1', $result->getId());
        $this->assertEquals('Test Value 1', $result->getName());
        $this->assertEquals(1, $result->getPriority());
        $this->assertTrue($result->isDefault());
    }
}
