<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Validator\Constraints\FilenameWithoutPath;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FilenameWithoutPathValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class FilenameWithoutPathValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createValidator(): FilenameWithoutPathValidator
    {
        return new FilenameWithoutPathValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new FilenameWithoutPath();
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected argument of type "%s", "%s" given',
            FilenameWithoutPath::class,
            \get_class($constraint)
        ));

        $this->validator->validate('test.txt', $constraint);
    }

    public function testValidateWithEmptyValue(): void
    {
        $value = null;

        $constraint = new FilenameWithoutPath();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithObjectAsValue(): void
    {
        $value = new \stdClass();

        $constraint = new FilenameWithoutPath();

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected argument of type "string", "%s" given',
            \get_class($value)
        ));

        $this->validator->validate($value, $constraint);
    }

    public function testValidateWithCorrectValue(): void
    {
        $value = 'someFile.txt';

        $constraint = new FilenameWithoutPath();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithValueWithPath(): void
    {
        $value = 'test/path/someFile.txt';

        $constraint = new FilenameWithoutPath();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateWithValueWithRootPath(): void
    {
        $value = '/someFile.txt';

        $constraint = new FilenameWithoutPath();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateWithValueWithDotDirPath(): void
    {
        $value = './someFile.txt';

        $constraint = new FilenameWithoutPath();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateWithValueWith2DotDirPath(): void
    {
        $value = '../someFile.txt';

        $constraint = new FilenameWithoutPath();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
