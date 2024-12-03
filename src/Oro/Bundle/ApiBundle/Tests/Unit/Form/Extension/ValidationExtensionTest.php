<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension;
use Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationExtensionTest extends \PHPUnit\Framework\TestCase
{
    private ValidationExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new ValidationExtension($this->createMock(ValidatorInterface::class));
    }

    public function testBuildFormForDisabledValidation(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects(self::never())
            ->method('addEventSubscriber');

        $this->extension->buildForm($builder, ['enable_validation' => false]);
    }

    public function testBuildFormForEnabledValidation(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects(self::once())
            ->method('addEventSubscriber')
            ->with(self::isInstanceOf(ValidationListener::class));

        $this->extension->buildForm($builder, ['enable_validation' => true]);
    }

    public function testConfigureOptionsShouldEnableValidationAndDisableFullValidationByDefault(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        self::assertEquals(
            [
                'error_mapping' => [],
                'invalid_message' => 'This value is not valid.',
                'invalid_message_parameters' => [],
                'allow_extra_fields' => false,
                'extra_fields_message' => 'This form should not contain extra fields.',
                'validation_groups' => null,
                'constraints' => [],
                'enable_validation' => true,
                'enable_full_validation' => false
            ],
            $resolver->resolve()
        );
    }

    public function testConfigureOptionsWhenValidationDisabled(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        self::assertEquals(
            [
                'error_mapping' => [],
                'invalid_message' => 'This value is not valid.',
                'invalid_message_parameters' => [],
                'allow_extra_fields' => false,
                'extra_fields_message' => 'This form should not contain extra fields.',
                'validation_groups' => null,
                'constraints' => [],
                'enable_validation' => false,
                'enable_full_validation' => false
            ],
            $resolver->resolve(['enable_validation' => false])
        );
    }

    public function testConfigureOptionsWithInvalidEnableValidationValue(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage(
            'The option "enable_validation" with value "invalid" is expected to be of type "bool",'
            . ' but is of type "string".'
        );
        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);
        $resolver->resolve(['enable_validation' => 'invalid']);
    }

    public function testConfigureOptionsWhenFullValidationEnabled(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        self::assertEquals(
            [
                'error_mapping' => [],
                'invalid_message' => 'This value is not valid.',
                'invalid_message_parameters' => [],
                'allow_extra_fields' => false,
                'extra_fields_message' => 'This form should not contain extra fields.',
                'validation_groups' => null,
                'constraints' => [],
                'enable_validation' => true,
                'enable_full_validation' => true
            ],
            $resolver->resolve(['enable_full_validation' => true])
        );
    }

    public function testConfigureOptionsWithEnableFullValidationCallback(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        $enableFullValidationCallback = function () {
        };
        self::assertEquals(
            [
                'error_mapping' => [],
                'invalid_message' => 'This value is not valid.',
                'invalid_message_parameters' => [],
                'allow_extra_fields' => false,
                'extra_fields_message' => 'This form should not contain extra fields.',
                'validation_groups' => null,
                'constraints' => [],
                'enable_validation' => true,
                'enable_full_validation' => $enableFullValidationCallback
            ],
            $resolver->resolve(['enable_full_validation' => $enableFullValidationCallback])
        );
    }

    public function testConfigureOptionsWithInvalidEnableFullValidationValue(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage(
            'The option "enable_full_validation" with value "invalid" is expected to be of type "bool" or "callable",'
            . ' but is of type "string".'
        );
        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);
        $resolver->resolve(['enable_full_validation' => 'invalid']);
    }
}
