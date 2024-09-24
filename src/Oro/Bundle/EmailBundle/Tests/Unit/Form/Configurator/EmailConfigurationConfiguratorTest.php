<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Oro\Bundle\ConfigBundle\Form\Type\FormType;
use Oro\Bundle\EmailBundle\Form\Configurator\EmailConfigurationConfigurator;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailConfigurationConfiguratorTest extends FormIntegrationTestCase
{
    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private static $encryptor;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private static $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        self::$encryptor = $this->createMock(SymmetricCrypterInterface::class);
        self::$validator = $this->createMock(ValidatorInterface::class);
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    new FormType(
                        new ConfigSubscriber($this->createMock(ConfigManager::class)),
                        $this->createMock(ContainerInterface::class)
                    )
                ],
                [SymfonyFormType::class => [new DataBlockExtension()]]
            ),
        ];
    }

    public function testConfigureWhenNoSmtpPasswordSetting(): void
    {
        $builder = $this->createFormBuilder();

        self::$encryptor->expects(self::never())
            ->method('encryptData');

        $builder->add('otherField', FormFieldType::class);
        $form = $builder->getForm();
        $form->submit(['otherField' => ['value' => '']]);

        self::assertArrayNotHasKey('oro_email___smtp_settings_password', $form->getData());
    }

    public function testConfigureWhenSmtpPasswordSettingExistAndNoNewPasswordKeyIsSubmitted(): void
    {
        $passwordKey = 'oro_email___smtp_settings_password';
        $oldPassword = 'old_password';

        $builder = $this->createFormBuilder();
        $builder->add($passwordKey, FormFieldType::class);

        self::$encryptor->expects(self::never())
            ->method('encryptData');

        $builder->getForm()->submit([]);

        $form = $builder->getForm();
        $form->setData([$passwordKey => ['value' => $oldPassword]]);
        $form->submit([$passwordKey => ['value' => '']]);

        $this->assertSame($oldPassword, $form->getData()[$passwordKey]['value']);
    }

    public function testConfigureWhenSmtpPasswordSettingExistAndNewPasswordKeyIsSubmitted(): void
    {
        $passwordKey = 'oro_email___smtp_settings_password';
        $encryptedPassword = 'encrypted_password';
        $oldPassword = 'old_password';
        $newPassword = 'new_password';

        $builder = $this->createFormBuilder();
        $builder->add($passwordKey, FormFieldType::class);

        self::$encryptor->expects(self::once())
            ->method('encryptData')
            ->with($newPassword)
            ->willReturn($encryptedPassword);

        $form = $builder->getForm();
        $form->setData([$passwordKey => ['value' => $oldPassword]]);
        $form->submit([$passwordKey => ['value' => $newPassword]]);

        $this->assertSame($encryptedPassword, $form->getData()[$passwordKey]['value']);
    }

    public function testConfigureWithParentScopeValue(): void
    {
        $builder = $this->createFormBuilder();
        $builder->add('oro_email___smtp_settings_host', FormFieldType::class);
        $builder->add('oro_email___smtp_settings_port', FormFieldType::class);
        $builder->add('oro_email___smtp_settings_encryption', FormFieldType::class);
        $builder->add('oro_email___smtp_settings_username', FormFieldType::class);
        $builder->add('oro_email___smtp_settings_password', FormFieldType::class);

        self::$validator->expects($this->never())
            ->method('validate');

        $builder->getForm()->submit(
            [
                'oro_email___smtp_settings_host' => ['use_parent_scope_value' => true],
                'oro_email___smtp_settings_port' => ['use_parent_scope_value' => true],
                'oro_email___smtp_settings_encryption' => ['use_parent_scope_value' => true],
                'oro_email___smtp_settings_username' => ['use_parent_scope_value' => true],
                'oro_email___smtp_settings_password' => ['use_parent_scope_value' => true]
            ]
        );
    }

    public function testConfigureSmtpConnectionConfigurationError(): void
    {
        $builder = $this->createFormBuilder();
        $builder->add('oro_email___smtp_settings_host', FormFieldType::class);
        $builder->add('oro_email___smtp_settings_port', FormFieldType::class);
        $builder->add('oro_email___smtp_settings_encryption', FormFieldType::class);
        $builder->add('oro_email___smtp_settings_username', FormFieldType::class);
        $builder->add('oro_email___smtp_settings_password', FormFieldType::class);

        $error = $this->createMock(ConstraintViolationInterface::class);
        $error->expects($this->once())
            ->method('getMessage')
            ->willReturn('Test error');
        self::$validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList([$error]));

        $form = $builder->getForm();
        $form->submit([]);
        $this->assertCount(1, $form->getErrors());
    }

    public static function configure(FormBuilderInterface $builder, $options): void
    {
        $emailConfigurationConfigurator = new EmailConfigurationConfigurator(self::$encryptor, self::$validator);
        $emailConfigurationConfigurator->configure($builder, $options);
    }

    private function createFormBuilder(): FormBuilderInterface
    {
        return $this->factory->createNamedBuilder(
            'config_settings',
            FormType::class,
            null,
            [
                'block_config' => [
                    'email_configuration' => [
                        'configurator' => self::class . '::configure'
                    ]
                ]
            ]
        );
    }
}
