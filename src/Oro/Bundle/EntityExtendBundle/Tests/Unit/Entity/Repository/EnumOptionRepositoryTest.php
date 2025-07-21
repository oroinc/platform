<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use PHPUnit\Framework\TestCase;

class EnumOptionRepositoryTest extends TestCase
{
    private const string ENUM_VALUE_CLASS_NAME = TestEnumValue::class;
    private EnumOptionRepository $repo;

    #[\Override]
    protected function setUp(): void
    {
        $this->repo = new EnumOptionRepository(
            $this->createMock(EntityManagerInterface::class),
            new ClassMetadata(self::ENUM_VALUE_CLASS_NAME)
        );
    }

    public function testCreateEnumValueWithEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$name must not be empty.');

        $this->repo->createEnumOption('test_enum_code', 'test', '', 1);
    }

    public function testCreateEnumValueWithTooLongId(): void
    {
        $enumCode = 'test_enum_code';
        $longInternalId = str_repeat('a', 100);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                '$id length must be less or equal 100 characters. id: %s.',
                ExtendHelper::buildEnumOptionId($enumCode, $longInternalId)
            )
        );

        $this->repo->createEnumOption(
            $enumCode,
            $longInternalId,
            'Test Value 1',
            1
        );
    }

    public function testCreateEnumOption(): void
    {
        $result = $this->repo->createEnumOption('enumCode', 'internalId', 'name', 1);

        $this->assertInstanceOf(self::ENUM_VALUE_CLASS_NAME, $result);
        $this->assertEquals(
            ExtendHelper::buildEnumOptionId('enumCode', 'internalId'),
            $result->getId()
        );
        $this->assertEquals('enumCode', $result->getEnumCode());
        $this->assertEquals('name', $result->getName());
        $this->assertEquals('internalId', $result->getInternalId());
        $this->assertEquals(1, $result->getPriority());
        $this->assertFalse($result->isDefault());
    }
}
