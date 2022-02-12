<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Validator\Constraints\RelatedEntityFieldFallbackValueConstraint;
use Oro\Bundle\EntityBundle\Validator\Constraints\RelatedEntityFieldFallbackValueConstraintValidator;
use Oro\Component\DoctrineUtils\Tests\Unit\Stub\DummyEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RelatedEntityFieldFallbackValueConstraintValidatorTest extends ConstraintValidatorTestCase
{
    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $resolver;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextualValidator;

    protected function setUp(): void
    {
        $this->resolver = $this->createMock(EntityFallbackResolver::class);
        $this->contextualValidator = $this->createMock(ContextualValidatorInterface::class);
        parent::setUp();
    }

    protected function createValidator(): RelatedEntityFieldFallbackValueConstraintValidator
    {
        return new RelatedEntityFieldFallbackValueConstraintValidator($this->resolver);
    }

    protected function createContext(): ExecutionContext
    {
        $context = parent::createContext();
        /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $validator */
        $validator = $context->getValidator();
        $validator->expects($this->any())
            ->method('startContext')
            ->willReturn($this->contextualValidator);
        $this->contextualValidator->expects($this->any())
            ->method('atPath')
            ->willReturnSelf();

        return $context;
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testValidation(
        ?string $fallbackId,
        mixed $scalarValue,
        bool $isFallbackConfigured,
        array $constraints,
        ?string $violationMessage = null,
        array $parameters = []
    ) {
        $parentEntity = new DummyEntity();
        $fieldName = 'fieldOne';
        $this->setProperty($parentEntity, $fieldName);

        $value = new EntityFieldFallbackValue();
        $value->setFallback($fallbackId);
        $value->setScalarValue($scalarValue);

        $this->resolver->expects($this->any())
            ->method('isFallbackConfigured')
            ->with($fallbackId, $parentEntity, $fieldName)
            ->willReturn($isFallbackConfigured);

        $this->resolver->expects($this->any())
            ->method('getFallbackConfig')
            ->with($parentEntity, $fieldName, EntityFieldFallbackValue::FALLBACK_LIST)
            ->willReturn(['category' => ['some category config'], 'systemConfig' => ['some system config']]);

        $this->resolver->expects($this->any())
            ->method('getRequiredFallbackFieldByType')
            ->willReturn(EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD);

        $violation = new ConstraintViolation(
            'Wrong scalar value!',
            'Wrong scalar value!',
            [],
            'root',
            'property.path',
            'InvalidValue',
            null,
            null,
            new Assert\NotNull()
        );
        $this->contextualValidator->expects($this->any())
            ->method('validate')
            ->with($scalarValue, $constraints)
            ->willReturnSelf();
        $this->contextualValidator->expects($this->any())
            ->method('getViolations')
            ->willReturn(new ConstraintViolationList([$violation]));

        $constraint = new RelatedEntityFieldFallbackValueConstraint($constraints);
        $this->validator->validate($value, $constraint);

        if ($violationMessage) {
            $this->buildViolation($violationMessage)->setParameters($parameters)->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function validationDataProvider(): array
    {
        return [
            ['category', null, true, []],
            [
                'wrongFallback',
                null,
                false,
                [],
                'oro.entity.entity_field_fallback_value.invalid_with_hint',
                ['%values%' => 'category, systemConfig']
            ],
            [null, null, false, [], 'oro.entity.entity_field_fallback_value.not_null'],
            [null, null, false, [new Assert\NotBlank()], 'Wrong scalar value!'],
            [null, 'some value', false, []],
            [null, 'some value', false, [new Assert\Blank()], 'Wrong scalar value!'],
            ['category', null, true, [new Assert\NotBlank()]],
            [
                'wrongFallback',
                null,
                false,
                [new Assert\NotBlank()],
                'oro.entity.entity_field_fallback_value.invalid_with_hint',
                ['%values%' => 'category, systemConfig']
            ],
        ];
    }
}
