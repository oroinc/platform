<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Provider\MultipleFileConstraintsProvider;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\MultipleFileConstraintFromEntityFieldConfig;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\MultipleFileConstraintFromEntityFieldConfigValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MultipleFileConstraintFromEntityFieldConfigValidatorTest extends ConstraintValidatorTestCase
{
    /** @var MultipleFileConstraintsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $constraintsProvider;

    protected function setUp(): void
    {
        $this->constraintsProvider = $this->createMock(MultipleFileConstraintsProvider::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new MultipleFileConstraintFromEntityFieldConfigValidator($this->constraintsProvider);
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected instance of %s, got %s',
            MultipleFileConstraintFromEntityFieldConfig::class,
            get_class($constraint)
        ));

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidate(): void
    {
        $entityClass = 'SampleClass';
        $fieldName = 'sampleField';

        $this->constraintsProvider->expects(self::once())
            ->method('getMaxNumberOfFilesForEntityField')
            ->with($entityClass, $fieldName)
            ->willReturn(1);

        $files = new ArrayCollection();
        $files->add(new FileItem());
        $files->add(new FileItem());

        $constraint = new MultipleFileConstraintFromEntityFieldConfig([
            'entityClass' => $entityClass,
            'fieldName'   => $fieldName
        ]);
        $this->validator->validate($files, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{max}}', 1)
            ->setPlural(1)
            ->assertRaised();
    }
}
