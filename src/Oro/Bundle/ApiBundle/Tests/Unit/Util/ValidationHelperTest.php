<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Util\ValidationHelper;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;

class ValidationHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataFactoryInterface */
    private $metadataFactory;

    /** @var ValidationHelper */
    private $validationHelper;

    protected function setUp()
    {
        $this->metadataFactory = $this->createMock(MetadataFactoryInterface::class);

        $this->validationHelper = new ValidationHelper($this->metadataFactory);
    }

    public function testGetValidationMetadataForClassWhenNoMetadata()
    {
        $className = 'Test\Class';

        $this->metadataFactory->expects(self::once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(false);
        $this->metadataFactory->expects(self::never())
            ->method('getMetadataFor');

        self::assertNull($this->validationHelper->getValidationMetadataForClass($className));
    }

    public function testGetValidationMetadataForClassWhenMetadataExists()
    {
        $className = 'Test\Class';
        $classMetadata = $this->createMock(ClassMetadataInterface::class);

        $this->metadataFactory->expects(self::once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects(self::once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);

        self::assertSame(
            $classMetadata,
            $this->validationHelper->getValidationMetadataForClass($className)
        );
    }

    public function testGetValidationMetadataForPropertyWhenNoClassMetadata()
    {
        $className = 'Test\Class';
        $propertyName = 'test';

        $this->metadataFactory->expects(self::once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(false);
        $this->metadataFactory->expects(self::never())
            ->method('getMetadataFor');

        self::assertEquals(
            [],
            $this->validationHelper->getValidationMetadataForProperty($className, $propertyName)
        );
    }

    public function testGetValidationMetadataForProperty()
    {
        $className = 'Test\Class';
        $propertyName = 'test';
        $classMetadata = $this->createMock(ClassMetadataInterface::class);
        $propertyMetadata = $this->createMock(PropertyMetadataInterface::class);

        $this->metadataFactory->expects(self::once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects(self::once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects(self::once())
            ->method('getPropertyMetadata')
            ->with($propertyName)
            ->willReturn([$propertyMetadata]);

        $result = $this->validationHelper->getValidationMetadataForProperty($className, $propertyName);
        self::assertCount(1, $result);
        self::assertSame($propertyMetadata, $result[0]);
    }

    public function testHasValidationConstraintForClassWhenNoExpectedConstraint()
    {
        $className = 'Test\Class';
        $group = 'Test';
        $classMetadata = $this->createMock(ClassMetadataInterface::class);

        $this->metadataFactory->expects(self::once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects(self::once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects(self::once())
            ->method('findConstraints')
            ->with($group)
            ->willReturn([new NotBlank()]);

        self::assertFalse(
            $this->validationHelper->hasValidationConstraintForClass(
                $className,
                NotNull::class,
                $group
            )
        );
    }

    public function testHasValidationConstraintForClassWhenExpectedConstraintExists()
    {
        $className = 'Test\Class';
        $group = 'Test';
        $classMetadata = $this->createMock(ClassMetadataInterface::class);

        $this->metadataFactory->expects(self::once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects(self::once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects(self::once())
            ->method('findConstraints')
            ->with($group)
            ->willReturn([new NotBlank()]);

        self::assertTrue(
            $this->validationHelper->hasValidationConstraintForClass(
                $className,
                NotBlank::class,
                $group
            )
        );
    }

    public function testHasValidationConstraintForPropertyWhenNoExpectedConstraint()
    {
        $className = 'Test\Class';
        $propertyName = 'test';
        $group = 'Test';
        $classMetadata = $this->createMock(ClassMetadataInterface::class);
        $propertyMetadata = $this->createMock(PropertyMetadataInterface::class);

        $this->metadataFactory->expects(self::once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects(self::once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects(self::once())
            ->method('getPropertyMetadata')
            ->with($propertyName)
            ->willReturn([$propertyMetadata]);
        $propertyMetadata->expects(self::once())
            ->method('findConstraints')
            ->with($group)
            ->willReturn([new NotBlank()]);

        self::assertFalse(
            $this->validationHelper->hasValidationConstraintForProperty(
                $className,
                $propertyName,
                NotNull::class,
                $group
            )
        );
    }

    public function testHasValidationConstraintForPropertyWhenExpectedConstraintExists()
    {
        $className = 'Test\Class';
        $propertyName = 'test';
        $group = 'Test';
        $classMetadata = $this->createMock(ClassMetadataInterface::class);
        $propertyMetadata = $this->createMock(PropertyMetadataInterface::class);

        $this->metadataFactory->expects(self::once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects(self::once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects(self::once())
            ->method('getPropertyMetadata')
            ->with($propertyName)
            ->willReturn([$propertyMetadata]);
        $propertyMetadata->expects(self::once())
            ->method('findConstraints')
            ->with($group)
            ->willReturn([new NotBlank()]);

        self::assertTrue(
            $this->validationHelper->hasValidationConstraintForProperty(
                $className,
                $propertyName,
                NotBlank::class,
                $group
            )
        );
    }
}
