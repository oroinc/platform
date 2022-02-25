<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ValueNormalizerUtilTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider humanizeClassNameDataProvider
     */
    public function testHumanizeClassName(string $className, ?string $classSuffix, string $expected): void
    {
        self::assertEquals(
            $expected,
            ValueNormalizerUtil::humanizeClassName($className, $classSuffix)
        );
    }

    public function humanizeClassNameDataProvider(): array
    {
        return [
            [\Exception::class, null, 'exception'],
            [\Exception::class, 'Exception', 'exception'],
            [\LogicException::class, null, 'logic exception'],
            [\LogicException::class, 'Exception', 'logic exception'],
            [\InvalidArgumentException::class, null, 'invalid argument exception'],
            [\InvalidArgumentException::class, 'Exception', 'invalid argument exception'],
            ['Test\InvalidArgumentException', null, 'invalid argument exception'],
            ['Test\InvalidArgumentException', 'Exception', 'invalid argument exception'],
            ['Test\Invalid_Argument_Exception', 'Exception', 'invalid argument exception'],
            ['Test\invalid_argument_exception', 'Exception', 'invalid argument exception'],
            ['Test\Invalid__Argument__Exception', 'Exception', 'invalid argument exception'],
            ['Test\invalid__argument__exception', 'Exception', 'invalid argument exception'],
            ['Test\IOException', 'Exception', 'io exception'],
            ['Test\AnotherPHPException', 'Exception', 'another php exception']
        ];
    }

    public function testConvertToEntityType(): void
    {
        $entityClass = 'Test\Class';
        $entityType = 'test_class';
        $requestType = new RequestType([RequestType::REST]);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $requestType)
            ->willReturn($entityType);

        self::assertEquals(
            $entityType,
            ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityClass, $requestType)
        );
    }

    public function testConvertToEntityTypeWhenEntityTypeNotFound(): void
    {
        $entityClass = 'Test\Class';
        $requestType = new RequestType([RequestType::REST]);

        $this->expectException(EntityAliasNotFoundException::class);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $requestType)
            ->willThrowException(new EntityAliasNotFoundException());

        ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityClass, $requestType);
    }

    public function testConvertToEntityTypeWhenUnexpectedExceptionOccurred(): void
    {
        $entityClass = 'Test\Class';
        $requestType = new RequestType([RequestType::REST]);

        $this->expectException(\InvalidArgumentException::class);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $requestType)
            ->willThrowException(new \InvalidArgumentException());

        ValueNormalizerUtil::convertToEntityType($valueNormalizer, $entityClass, $requestType);
    }

    public function testTryConvertToEntityType(): void
    {
        $entityClass = 'Test\Class';
        $entityType = 'test_class';
        $requestType = new RequestType([RequestType::REST]);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $requestType)
            ->willReturn($entityType);

        self::assertEquals(
            $entityType,
            ValueNormalizerUtil::tryConvertToEntityType($valueNormalizer, $entityClass, $requestType)
        );
    }

    public function testTryConvertToEntityTypeWhenEntityTypeNotFound(): void
    {
        $entityClass = 'Test\Class';
        $requestType = new RequestType([RequestType::REST]);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $requestType)
            ->willThrowException(new EntityAliasNotFoundException());

        self::assertNull(
            ValueNormalizerUtil::tryConvertToEntityType($valueNormalizer, $entityClass, $requestType)
        );
    }

    public function testTryConvertToEntityTypeWhenUnexpectedExceptionOccurred(): void
    {
        $entityClass = 'Test\Class';
        $requestType = new RequestType([RequestType::REST]);

        $this->expectException(\InvalidArgumentException::class);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $requestType)
            ->willThrowException(new \InvalidArgumentException());

        ValueNormalizerUtil::tryConvertToEntityType($valueNormalizer, $entityClass, $requestType);
    }

    public function testConvertToEntityClass(): void
    {
        $entityType = 'test_class';
        $entityClass = 'Test\Class';
        $requestType = new RequestType([RequestType::REST]);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);

        self::assertEquals(
            $entityClass,
            ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType, $requestType)
        );
    }

    public function testConvertToEntityClassWhenEntityClassNotFound(): void
    {
        $entityType = 'test_class';
        $requestType = new RequestType([RequestType::REST]);

        $this->expectException(EntityAliasNotFoundException::class);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willThrowException(new EntityAliasNotFoundException());

        ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType, $requestType);
    }

    public function testConvertToEntityClassWhenUnexpectedExceptionOccurred(): void
    {
        $entityType = 'test_class';
        $requestType = new RequestType([RequestType::REST]);

        $this->expectException(\InvalidArgumentException::class);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willThrowException(new \InvalidArgumentException());

        ValueNormalizerUtil::convertToEntityClass($valueNormalizer, $entityType, $requestType);
    }

    public function testTryConvertToEntityClass(): void
    {
        $entityType = 'test_class';
        $entityClass = 'Test\Class';
        $requestType = new RequestType([RequestType::REST]);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willReturn($entityClass);

        self::assertEquals(
            $entityClass,
            ValueNormalizerUtil::tryConvertToEntityClass($valueNormalizer, $entityType, $requestType)
        );
    }

    public function testTryConvertToEntityClassWhenEntityClassNotFound(): void
    {
        $entityType = 'test_class';
        $requestType = new RequestType([RequestType::REST]);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willThrowException(new EntityAliasNotFoundException());

        self::assertNull(
            ValueNormalizerUtil::tryConvertToEntityClass($valueNormalizer, $entityType, $requestType)
        );
    }

    public function testTryConvertToEntityClassWhenUnexpectedExceptionOccurred(): void
    {
        $entityType = 'test_class';
        $requestType = new RequestType([RequestType::REST]);

        $this->expectException(\InvalidArgumentException::class);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $requestType)
            ->willThrowException(new \InvalidArgumentException());

        ValueNormalizerUtil::tryConvertToEntityClass($valueNormalizer, $entityType, $requestType);
    }
}
