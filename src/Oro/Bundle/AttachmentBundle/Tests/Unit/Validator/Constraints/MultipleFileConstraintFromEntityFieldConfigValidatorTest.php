<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Provider\MultipleFileConstraintsProvider;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromEntityFieldConfigValidator;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\MultipleFileConstraintFromEntityFieldConfig;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\MultipleFileConstraintFromEntityFieldConfigValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class MultipleFileConstraintFromEntityFieldConfigValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MultipleFileConstraintsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $constraintsProvider;

    /** @var FileConstraintFromEntityFieldConfigValidator */
    private $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->constraintsProvider = $this->createMock(MultipleFileConstraintsProvider::class);

        $this->validator = new MultipleFileConstraintFromEntityFieldConfigValidator(
            $this->constraintsProvider
        );
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(vsprintf('Expected instance of %s, got %s', [
            MultipleFileConstraintFromEntityFieldConfig::class,
            get_class($constraint),
        ]));

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidate(): void
    {
        $constraint = $this->createMock(MultipleFileConstraintFromEntityFieldConfig::class);
        $constraint
            ->method('getEntityClass')
            ->willReturn($entityClass = 'SampleClass');

        $constraint
            ->method('getFieldName')
            ->willReturn($fieldName = 'sampleField');

        $this->constraintsProvider
            ->method('getMaxNumberOfFilesForEntityField')
            ->with($entityClass, $fieldName)
            ->willReturn(1);

        $this->validator->initialize($context = $this->createMock(ExecutionContextInterface::class));
        $context->expects(self::once())
            ->method('buildViolation')
            ->willReturn($builder = $this->createMock(ConstraintViolationBuilderInterface::class));
        $builder->expects(self::once())->method('setParameters')->willReturn($builder);
        $builder->expects(self::once())->method('setPlural')->willReturn($builder);
        $builder->expects(self::once())->method('addViolation');

        $files = new ArrayCollection();
        $files->add(new FileItem());
        $files->add(new FileItem());

        $this->validator->validate($files, $constraint);
    }
}
