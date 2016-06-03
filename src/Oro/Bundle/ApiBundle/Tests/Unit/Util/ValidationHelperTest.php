<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\ApiBundle\Util\ValidationHelper;

class ValidationHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataFactory;

    /** @var ValidationHelper */
    protected $validationHelper;

    protected function setUp()
    {
        $this->metadataFactory = $this
            ->getMock('Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface');

        $this->validationHelper = new ValidationHelper($this->metadataFactory);
    }

    public function testGetValidationMetadataForClassWhenNoMetadata()
    {
        $className = 'Test\Class';

        $this->metadataFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(false);
        $this->metadataFactory->expects($this->never())
            ->method('getMetadataFor');

        $this->assertNull($this->validationHelper->getValidationMetadataForClass($className));
    }

    public function testGetValidationMetadataForClassWhenMetadataExists()
    {
        $className = 'Test\Class';
        $classMetadata = $this->getMock('Symfony\Component\Validator\Mapping\ClassMetadataInterface');

        $this->metadataFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);

        $this->assertSame(
            $classMetadata,
            $this->validationHelper->getValidationMetadataForClass($className)
        );
    }

    public function testGetValidationMetadataForPropertyWhenNoClassMetadata()
    {
        $className = 'Test\Class';
        $propertyName = 'test';

        $this->metadataFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(false);
        $this->metadataFactory->expects($this->never())
            ->method('getMetadataFor');

        $this->assertEquals(
            [],
            $this->validationHelper->getValidationMetadataForProperty($className, $propertyName)
        );
    }

    public function testGetValidationMetadataForProperty()
    {
        $className = 'Test\Class';
        $propertyName = 'test';
        $classMetadata = $this->getMock('Symfony\Component\Validator\Mapping\ClassMetadataInterface');
        $propertyMetadata = $this->getMock('Symfony\Component\Validator\Mapping\PropertyMetadataInterface');

        $this->metadataFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects($this->once())
            ->method('getPropertyMetadata')
            ->with($propertyName)
            ->willReturn([$propertyMetadata]);

        $result = $this->validationHelper->getValidationMetadataForProperty($className, $propertyName);
        $this->assertCount(1, $result);
        $this->assertSame($propertyMetadata, $result[0]);
    }

    public function testHasValidationConstraintForClassWhenNoExpectedConstraint()
    {
        $className = 'Test\Class';
        $group = 'Test';
        $classMetadata = $this->getMock('Symfony\Component\Validator\Mapping\ClassMetadataInterface');

        $this->metadataFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects($this->once())
            ->method('findConstraints')
            ->with($group)
            ->willReturn([new NotBlank()]);

        $this->assertFalse(
            $this->validationHelper->hasValidationConstraintForClass(
                $className,
                'Symfony\Component\Validator\Constraints\NotNull',
                $group
            )
        );
    }

    public function testHasValidationConstraintForClassWhenExpectedConstraintExists()
    {
        $className = 'Test\Class';
        $group = 'Test';
        $classMetadata = $this->getMock('Symfony\Component\Validator\Mapping\ClassMetadataInterface');

        $this->metadataFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects($this->once())
            ->method('findConstraints')
            ->with($group)
            ->willReturn([new NotBlank()]);

        $this->assertTrue(
            $this->validationHelper->hasValidationConstraintForClass(
                $className,
                'Symfony\Component\Validator\Constraints\NotBlank',
                $group
            )
        );
    }

    public function testHasValidationConstraintForPropertyWhenNoExpectedConstraint()
    {
        $className = 'Test\Class';
        $propertyName = 'test';
        $group = 'Test';
        $classMetadata = $this->getMock('Symfony\Component\Validator\Mapping\ClassMetadataInterface');
        $propertyMetadata = $this->getMock('Symfony\Component\Validator\Mapping\PropertyMetadataInterface');

        $this->metadataFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects($this->once())
            ->method('getPropertyMetadata')
            ->with($propertyName)
            ->willReturn([$propertyMetadata]);
        $propertyMetadata->expects($this->once())
            ->method('findConstraints')
            ->with($group)
            ->willReturn([new NotBlank()]);

        $this->assertFalse(
            $this->validationHelper->hasValidationConstraintForProperty(
                $className,
                $propertyName,
                'Symfony\Component\Validator\Constraints\NotNull',
                $group
            )
        );
    }

    public function testHasValidationConstraintForPropertyWhenExpectedConstraintExists()
    {
        $className = 'Test\Class';
        $propertyName = 'test';
        $group = 'Test';
        $classMetadata = $this->getMock('Symfony\Component\Validator\Mapping\ClassMetadataInterface');
        $propertyMetadata = $this->getMock('Symfony\Component\Validator\Mapping\PropertyMetadataInterface');

        $this->metadataFactory->expects($this->once())
            ->method('hasMetadataFor')
            ->with($className)
            ->willReturn(true);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with($className)
            ->willReturn($classMetadata);
        $classMetadata->expects($this->once())
            ->method('getPropertyMetadata')
            ->with($propertyName)
            ->willReturn([$propertyMetadata]);
        $propertyMetadata->expects($this->once())
            ->method('findConstraints')
            ->with($group)
            ->willReturn([new NotBlank()]);

        $this->assertTrue(
            $this->validationHelper->hasValidationConstraintForProperty(
                $className,
                $propertyName,
                'Symfony\Component\Validator\Constraints\NotBlank',
                $group
            )
        );
    }
}
