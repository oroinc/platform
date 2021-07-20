<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotLessThanOriginalValue;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\NotLessThanOriginalValueValidator;
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
    private function getModel(int $id): FieldConfigModel
    {
        $model = new FieldConfigModel();
        $reflectionClass = new \ReflectionClass($model);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($model, $id);

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        return new NotLessThanOriginalValueValidator();
    }

    /**
     * {@inheritDoc}
     */
    protected function createContext()
    {
        $this->constraint = new NotLessThanOriginalValue(['scope' => 'extend', 'option' => 'length']);

        $this->propertyPath = null;

        return parent::createContext();
    }

    protected function getFormStub(array $config): MockObject
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

    public function testTryToValidateOnWrongConstraint()
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

    public function testValidateWithNullValue()
    {
        $this->validator->validate(null, $this->constraint);

        $this->assertNoViolation();
    }

    public function testTryToValidateWithWrongContextObject()
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
        $this->validator->validate('12', $this->constraint);
    }

    public function testTryToValidateWithFormWithoutConfigModelOption()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validator should be used only with ConfigType root form');

        $this->setObject($this->getFormStub([]));
        $this->validator->validate('12', $this->constraint);
    }

    public function testTryToValidateWithWrongFieldConfigModel()
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
        $this->validator->validate('12', $this->constraint);
    }

    public function testValidateWithFieldConfigModelWithEmptyId()
    {
        $configModel = new FieldConfigModel();

        $this->setObject($this->getFormStub(['config_model' => $configModel]));
        $this->validator->validate('12', $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithFieldConfigModelWithoutOptions()
    {
        $configModel = $this->getModel(1);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));
        $this->validator->validate('12', $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithBiggerValue()
    {
        $configModel = $this->getModel(1);
        $configModel->fromArray('extend', ['length' => '10']);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));
        $this->validator->validate('12', $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithEqualValue()
    {
        $configModel = $this->getModel(1);
        $configModel->fromArray('extend', ['length' => '12']);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));
        $this->validator->validate('12', $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithLessValue()
    {
        $configModel = $this->getModel(1);
        $configModel->fromArray('extend', ['length' => '20']);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));
        $this->validator->validate('12', $this->constraint);

        $this->buildViolation($this->constraint->message)
            ->setParameter('{{ originalValue }}', '20')
            ->atPath('')
            ->assertRaised();
    }

    public function testValidateWithLessValueWhenFieldStateIsNew()
    {
        $configModel = $this->getModel(1);
        $configModel->fromArray('extend', ['length' => '20', 'state' => 'New']);

        $this->setObject($this->getFormStub(['config_model' => $configModel]));
        $this->validator->validate('12', $this->constraint);

        $this->assertNoViolation();
    }
}
