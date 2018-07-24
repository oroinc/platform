<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension;
use Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValidatorInterface */
    private $validator;

    /** @var ValidationExtension */
    private $extension;

    protected function setUp()
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->extension = new ValidationExtension($this->validator);
    }

    public function testBuildFormForDisabledValidation()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects(self::never())
            ->method('addEventSubscriber');

        $this->extension->buildForm($builder, ['enable_validation' => false]);
    }

    public function testBuildFormForEnabledValidation()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects(self::once())
            ->method('addEventSubscriber')
            ->with(self::isInstanceOf(ValidationListener::class));

        $this->extension->buildForm($builder, ['enable_validation' => true]);
    }

    public function testConfigureOptionsShouldEnableValidationAndDisableFullValidationByDefault()
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        self::assertEquals(
            [
                'error_mapping'              => [],
                'invalid_message'            => 'This value is not valid.',
                'invalid_message_parameters' => [],
                'allow_extra_fields'         => false,
                'extra_fields_message'       => 'This form should not contain extra fields.',
                'validation_groups'          => null,
                'constraints'                => [],
                'enable_validation'          => true,
                'enable_full_validation'     => false
            ],
            $resolver->resolve()
        );
    }

    public function testConfigureOptionsWhenValidationDisabled()
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        self::assertEquals(
            [
                'error_mapping'              => [],
                'invalid_message'            => 'This value is not valid.',
                'invalid_message_parameters' => [],
                'allow_extra_fields'         => false,
                'extra_fields_message'       => 'This form should not contain extra fields.',
                'validation_groups'          => null,
                'constraints'                => [],
                'enable_validation'          => false,
                'enable_full_validation'     => false
            ],
            $resolver->resolve(['enable_validation' => false])
        );
    }

    public function testConfigureOptionsWhenFullValidationEnabled()
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        self::assertEquals(
            [
                'error_mapping'              => [],
                'invalid_message'            => 'This value is not valid.',
                'invalid_message_parameters' => [],
                'allow_extra_fields'         => false,
                'extra_fields_message'       => 'This form should not contain extra fields.',
                'validation_groups'          => null,
                'constraints'                => [],
                'enable_validation'          => true,
                'enable_full_validation'     => true
            ],
            $resolver->resolve(['enable_full_validation' => true])
        );
    }
}
