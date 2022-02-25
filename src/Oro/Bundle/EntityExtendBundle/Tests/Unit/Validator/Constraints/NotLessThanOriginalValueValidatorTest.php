<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotLessThanOriginalValue;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotLessThanOriginalValueValidator;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NotLessThanOriginalValueValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotLessThanOriginalValueValidator
    {
        return new NotLessThanOriginalValueValidator();
    }

    private function getConstraint(): NotLessThanOriginalValue
    {
        return new NotLessThanOriginalValue(['scope' => 'extend', 'option' => 'length']);
    }

    private function getModel(int $id): FieldConfigModel
    {
        $model = new FieldConfigModel();
        ReflectionUtil::setId($model, $id);

        return $model;
    }

    private function getFormStub(array $config): MockObject
    {
        $formConfig = new FormConfigBuilder(null, null, $this->createMock(EventDispatcherInterface::class), $config);
        $form = $this->createMock(FormInterface::class);
        $rootForm = $this->createMock(FormInterface::class);

        $form->expects(self::once())
            ->method('isRoot')
            ->willReturn(false);
        $form->expects(self::once())
            ->method('getParent')
            ->willReturn($rootForm);

        $rootForm->expects(self::once())
            ->method('isRoot')
            ->willReturn(true);
        $rootForm->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        return $form;
    }

    public function testTryToValidateOnWrongConstraint(): void
    {
        $constraint = $this->createMock(Constraint::class);

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Expected argument of type "%s", "%s" given',
                NotLessThanOriginalValue::class,
                get_class($constraint)
            )
        );

        $this->validator->validate('1', $constraint);
    }

    public function testValidateWithNullValue(): void
    {
        $constraint = $this->getConstraint();
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    public function testTryToValidateWithWrongContextObject(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Expected argument of type "%s", "%s" given',
                FormInterface::class,
                \stdClass::class
            )
        );

        $this->setObject(new \stdClass());

        $constraint = $this->getConstraint();
        $this->validator->validate('12', $constraint);
    }

    public function testTryToValidateWithFormWithoutConfigModelOption(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validator should be used only with ConfigType root form');

        $this->setObject($this->getFormStub([]));

        $constraint = $this->getConstraint();
        $this->validator->validate('12', $constraint);
    }

    public function testTryToValidateWithWrongFieldConfigModel(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Expected argument of type "%s", "%s" given',
                FieldConfigModel::class,
                \stdClass::class
            )
        );

        $this->setObject($this->getFormStub(['config_model' => new \stdClass()]));

        $constraint = $this->getConstraint();
        $this->validator->validate('12', $constraint);
    }

    public function testValidateWithFieldConfigModelWithEmptyId(): void
    {
        $configModel = new FieldConfigModel();

        $this->setObject($this->getFormStub(['config_model' => $configModel]));

        $constraint = $this->getConstraint();
        $this->validator->validate('12', $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithFieldConfigModelWithoutOptions(): void
    {
        $configModel = $this->getModel(1);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));

        $constraint = $this->getConstraint();
        $this->validator->validate('12', $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithBiggerValue(): void
    {
        $configModel = $this->getModel(1);
        $configModel->fromArray('extend', ['length' => '10']);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));

        $constraint = $this->getConstraint();
        $this->validator->validate('12', $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithEqualValue(): void
    {
        $configModel = $this->getModel(1);
        $configModel->fromArray('extend', ['length' => '12']);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));

        $constraint = $this->getConstraint();
        $this->validator->validate('12', $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithLessValue(): void
    {
        $configModel = $this->getModel(1);
        $configModel->fromArray('extend', ['length' => '20']);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));

        $constraint = $this->getConstraint();
        $this->validator->validate('12', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ originalValue }}', '20')
            ->assertRaised();
    }

    public function testValidateWithLessValueWhenFieldStateIsNew(): void
    {
        $configModel = $this->getModel(1);
        $configModel->fromArray('extend', ['length' => '20', 'state' => 'New']);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));

        $constraint = $this->getConstraint();
        $this->validator->validate('12', $constraint);
        $this->assertNoViolation();
    }
}
