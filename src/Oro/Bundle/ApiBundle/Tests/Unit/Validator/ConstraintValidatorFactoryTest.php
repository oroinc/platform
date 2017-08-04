<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotBlankValidator;

use Oro\Component\Testing\Unit\TestContainerBuilder;
use Oro\Bundle\ApiBundle\Form\FormExtensionState;
use Oro\Bundle\ApiBundle\Validator\ConstraintValidatorFactory;
use Oro\Bundle\ApiBundle\Validator\Constraints\NotBlankValidator as ApiNotBlankValidator;

class ConstraintValidatorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var FormExtensionState */
    private $formExtensionChecker;

    /** @var ConstraintValidatorFactory */
    private $constraintValidatorFactory;

    protected function setUp()
    {
        $this->formExtensionChecker = new FormExtensionState();
        $container = TestContainerBuilder::create()
            ->getContainer($this);

        $this->constraintValidatorFactory = new ConstraintValidatorFactory(
            $container,
            []
        );
        $this->constraintValidatorFactory->setFormExtensionChecker($this->formExtensionChecker);
        $this->constraintValidatorFactory->replaceValidatorClass(
            NotBlankValidator::class,
            ApiNotBlankValidator::class
        );
        $this->constraintValidatorFactory->replaceValidatorClass(
            'Test\NotExistingSourceValidator',
            'Test\NotExistingReplacementValidator'
        );
        $this->constraintValidatorFactory->replaceValidatorClass(
            'Test\InvalidSourceValidator',
            'stdClass'
        );
    }

    public function testGetInstanceForReplacedValidatorForApiForms()
    {
        $this->formExtensionChecker->switchToApiFormExtension();

        $validator = $this->constraintValidatorFactory->getInstance(new NotBlank());
        self::assertEquals(ApiNotBlankValidator::class, get_class($validator));

        // test that the created validator is cached
        $validator1 = $this->constraintValidatorFactory->getInstance(new NotBlank());
        self::assertSame($validator, $validator1);
    }

    public function testGetInstanceForReplacedValidatorForDefaultForms()
    {
        $validator = $this->constraintValidatorFactory->getInstance(new NotBlank());
        self::assertEquals(NotBlankValidator::class, get_class($validator));
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ValidatorException
     * @expectedExceptionMessage Constraint validator "Test\NotExistingReplacementValidator" does not exist.
     */
    public function testGetInstanceForReplacedValidatorWhenValidatorClassDoesNotExist()
    {
        $this->formExtensionChecker->switchToApiFormExtension();

        $constraint = $this->createMock(Constraint::class);
        $constraint->expects(self::once())
            ->method('validatedBy')
            ->willReturn('Test\NotExistingSourceValidator');

        $this->constraintValidatorFactory->getInstance($constraint);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Symfony\Component\Validator\ConstraintValidatorInterface", "stdClass" given
     */
    // @codingStandardsIgnoreEnd
    public function testGetInstanceForReplacedValidatorWhenValidatorDoesNotImplementConstraintValidatorInterface()
    {
        $this->formExtensionChecker->switchToApiFormExtension();

        $constraint = $this->createMock(Constraint::class);
        $constraint->expects(self::once())
            ->method('validatedBy')
            ->willReturn('Test\InvalidSourceValidator');

        $this->constraintValidatorFactory->getInstance($constraint);
    }
}
