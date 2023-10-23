<?php

declare(strict_types=1);

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\FormBundle\Validator\Constraints\AdaptivelyValidCollection;
use Oro\Bundle\FormBundle\Validator\Constraints\AdaptivelyValidCollectionValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdaptivelyValidCollectionValidatorTest extends ConstraintValidatorTestCase
{
    private EntityStateChecker|MockObject $entityStateChecker;

    private ValidatorInterface|MockObject $validatorComponent;

    private ContextualValidatorInterface|MockObject $innerContextualValidator;

    private AdaptivelyValidCollection $adaptivelyValidCollectionConstraint;

    protected function setUp(): void
    {
        $this->entityStateChecker = $this->createMock(EntityStateChecker::class);
        $this->validatorComponent = $this->createMock(ValidatorInterface::class);

        $this->adaptivelyValidCollectionConstraint = new AdaptivelyValidCollection(
            [
                'validationGroupsForNew' => ['for_new'],
                'validationGroupsForUpdated' => ['for_updated'],
                'validationGroupsForUnchanged' => ['for_unchanged'],
                'trackFields' => ['sampleFieldName'],
            ]
        );

        parent::setUp();
    }

    protected function createContext()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0);

        $context = new ExecutionContext($this->validatorComponent, $this->root, $translator);
        $context->setGroup($this->group);
        $context->setNode($this->value, $this->object, $this->metadata, $this->propertyPath);
        $context->setConstraint($this->constraint);

        $this->innerContextualValidator = $this->createMock(ContextualValidatorInterface::class);
        $this->validatorComponent
            ->method('inContext')
            ->with($context)
            ->willReturn($this->innerContextualValidator);

        return $context;
    }

    protected function createValidator(): AdaptivelyValidCollectionValidator
    {
        return new AdaptivelyValidCollectionValidator($this->entityStateChecker);
    }

    public function testValidateWhenInvalidConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);
        $this->expectExceptionObject(
            new UnexpectedTypeException($constraint, AdaptivelyValidCollection::class)
        );

        $this->validator->validate([], $constraint);
    }

    public function testValidateWhenInvalidValue(): void
    {
        $value = 'not_array';
        $this->expectExceptionObject(new UnexpectedValueException($value, 'iterable'));

        $this->validator->validate($value, $this->adaptivelyValidCollectionConstraint);
    }

    public function testValidateWhenEmptyValue(): void
    {
        $this->validator->validate([], $this->adaptivelyValidCollectionConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsNew(): void
    {
        $entity = new \stdClass();

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($entity)
            ->willReturn(true);

        $this->entityStateChecker
            ->expects(self::never())
            ->method('isChangedEntity');

        $this->innerContextualValidator
            ->expects(self::once())
            ->method('atPath')
            ->with('[0]')
            ->willReturnSelf();

        $this->innerContextualValidator
            ->expects(self::once())
            ->method('validate')
            ->with($entity, null, $this->adaptivelyValidCollectionConstraint->validationGroupsForNew);

        $this->validator->validate(new ArrayCollection([$entity]), $this->adaptivelyValidCollectionConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsNewAndNoGroups(): void
    {
        $entity = new \stdClass();

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($entity)
            ->willReturn(true);

        $this->entityStateChecker
            ->expects(self::never())
            ->method('isChangedEntity');

        $this->innerContextualValidator
            ->expects(self::never())
            ->method(self::anything());

        $this->adaptivelyValidCollectionConstraint->validationGroupsForNew = [];

        $this->validator->validate(new ArrayCollection([$entity]), $this->adaptivelyValidCollectionConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsUpdated(): void
    {
        $entity = new \stdClass();

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($entity)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($entity, $this->adaptivelyValidCollectionConstraint->trackFields)
            ->willReturn(true);

        $this->innerContextualValidator
            ->expects(self::once())
            ->method('atPath')
            ->with('[0]')
            ->willReturnSelf();

        $this->innerContextualValidator
            ->expects(self::once())
            ->method('validate')
            ->with($entity, null, $this->adaptivelyValidCollectionConstraint->validationGroupsForUpdated);

        $this->validator->validate(new ArrayCollection([$entity]), $this->adaptivelyValidCollectionConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsUpdatedAndNoGroups(): void
    {
        $entity = new \stdClass();

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($entity)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($entity, $this->adaptivelyValidCollectionConstraint->trackFields)
            ->willReturn(true);

        $this->innerContextualValidator
            ->expects(self::never())
            ->method(self::anything());

        $this->adaptivelyValidCollectionConstraint->validationGroupsForUpdated = [];

        $this->validator->validate(new ArrayCollection([$entity]), $this->adaptivelyValidCollectionConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsUnchanged(): void
    {
        $entity = new \stdClass();

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($entity)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($entity, $this->adaptivelyValidCollectionConstraint->trackFields)
            ->willReturn(false);

        $this->innerContextualValidator
            ->expects(self::once())
            ->method('atPath')
            ->with('[0]')
            ->willReturnSelf();

        $this->innerContextualValidator
            ->expects(self::once())
            ->method('validate')
            ->with($entity, null, $this->adaptivelyValidCollectionConstraint->validationGroupsForUnchanged);

        $this->validator->validate(new ArrayCollection([$entity]), $this->adaptivelyValidCollectionConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenIsUnchangedAndNoGroups(): void
    {
        $entity = new \stdClass();

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($entity)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isChangedEntity')
            ->with($entity, $this->adaptivelyValidCollectionConstraint->trackFields)
            ->willReturn(false);

        $this->innerContextualValidator
            ->expects(self::never())
            ->method(self::anything());

        $this->adaptivelyValidCollectionConstraint->validationGroupsForUnchanged = [];

        $this->validator->validate(new ArrayCollection([$entity]), $this->adaptivelyValidCollectionConstraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNoTrackFields(): void
    {
        $entity = new \stdClass();

        $this->entityStateChecker
            ->expects(self::once())
            ->method('isNewEntity')
            ->with($entity)
            ->willReturn(false);

        $this->entityStateChecker
            ->expects(self::never())
            ->method('isChangedEntity');

        $this->innerContextualValidator
            ->expects(self::never())
            ->method(self::anything());

        $this->adaptivelyValidCollectionConstraint->trackFields = [];

        $this->validator->validate(new ArrayCollection([$entity]), $this->adaptivelyValidCollectionConstraint);

        $this->assertNoViolation();
    }
}
