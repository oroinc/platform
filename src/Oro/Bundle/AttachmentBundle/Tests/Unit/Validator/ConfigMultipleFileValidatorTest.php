<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AttachmentBundle\Validator\ConfigMultipleFileValidator;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\MultipleFileConstraintFromEntityFieldConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigMultipleFileValidatorTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;
    private ConfigMultipleFileValidator $configValidator;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->configValidator = new ConfigMultipleFileValidator($this->validator);
    }

    public function testValidateWhenNoFieldName(): void
    {
        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $value = $this->createMock(ArrayCollection::class),
                [
                    new MultipleFileConstraintFromEntityFieldConfig([
                        'entityClass' => $dataClass = \stdClass::class,
                        'fieldName' => '',
                    ]),
                ]
            )
            ->willReturn($this->createMock(ConstraintViolationList::class));

        self::assertInstanceOf(
            ConstraintViolationList::class,
            $this->configValidator->validateFiles($value, $dataClass)
        );
    }

    public function testValidateWhenFieldName(): void
    {
        $this->validator->expects(self::once())
            ->method('validate')
            ->with(
                $value = $this->createMock(ArrayCollection::class),
                [
                    new MultipleFileConstraintFromEntityFieldConfig([
                        'entityClass' => $dataClass = \stdClass::class,
                        'fieldName' => $fieldName = 'fieldName',
                    ]),
                ]
            )
            ->willReturn($this->createMock(ConstraintViolationList::class));

        self::assertInstanceOf(
            ConstraintViolationList::class,
            $this->configValidator->validateFiles($value, $dataClass, $fieldName)
        );
    }
}
