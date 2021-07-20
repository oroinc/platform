<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Oro\Bundle\ConfigBundle\Form\Type\FormType;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
use Oro\Bundle\EmailBundle\Form\Configurator\EmailConfigurationConfigurator;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailConfigurationConfiguratorTest extends FormIntegrationTestCase
{
    private const ENCRYPTED_PASSWORD = 'encrypted_password';
    private const OLD_PASSWORD = 'old_password';
    private const NEW_PASSWORD = 'new_password';

    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private static $encryptor;

    /**
     * @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private static $validator;

    protected function setUp(): void
    {
        parent::setUp();
        self::$encryptor = $this->createMock(SymmetricCrypterInterface::class);
        self::$validator = $this->createMock(ValidatorInterface::class);
    }

    public function testConfigureWhenNoSmtpPasswordSetting(): void
    {
        $builder = $this->createFormBuilder();

        self::$encryptor->expects(self::never())->method('encryptData');

        $builder->add('otherField', FormFieldType::class);
        $form = $builder->getForm();
        $form->submit(['otherField' => ['value' => '']]);

        self::assertArrayNotHasKey($this->getSmtpPasswordFieldKey(), $form->getData());
    }

    public function testConfigureWhenSmtpPasswordSettingExistAndNoNewPasswordKeyIsSubmitted(): void
    {
        $builder = $this->createFormBuilder();
        $passwordKey = $this->getSmtpPasswordFieldKey();
        $builder->add($passwordKey, FormFieldType::class);

        self::$encryptor->expects(self::never())->method('encryptData');

        $builder->getForm()->submit([]);

        $form = $builder->getForm();
        $form->setData([$passwordKey => ['value' => self::OLD_PASSWORD]]);
        $form->submit([$passwordKey => ['value' => '']]);

        $this->assertSame(self::OLD_PASSWORD, $form->getData()[$passwordKey]['value']);
    }

    public function testConfigureWhenSmtpPasswordSettingExistAndNewPasswordKeyIsSubmitted(): void
    {
        $builder = $this->createFormBuilder();
        $passwordKey = $this->getSmtpPasswordFieldKey();
        $builder->add($passwordKey, FormFieldType::class);

        self::$encryptor
            ->expects(self::once())
            ->method('encryptData')
            ->with(self::NEW_PASSWORD)
            ->willReturn(self::ENCRYPTED_PASSWORD);

        $form = $builder->getForm();
        $form->setData([$passwordKey => ['value' => self::OLD_PASSWORD]]);
        $form->submit([$passwordKey => ['value' => self::NEW_PASSWORD]]);

        $this->assertSame(self::ENCRYPTED_PASSWORD, $form->getData()[$passwordKey]['value']);
    }

    public function testConfigureWithParentScopeValue(): void
    {
        $hostKey = $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_HOST);
        $portKey = $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_PORT);
        $encKey = $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_ENC);
        $userKey = $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_USER);
        $passKey = $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_PASS);

        $builder = $this->createFormBuilder();
        $builder->add($hostKey, FormFieldType::class);
        $builder->add($portKey, FormFieldType::class);
        $builder->add($encKey, FormFieldType::class);
        $builder->add($userKey, FormFieldType::class);
        $builder->add($passKey, FormFieldType::class);

        self::$validator->expects($this->never())
            ->method('validate');

        $builder->getForm()->submit(
            [
                $hostKey => ['use_parent_scope_value' => true],
                $portKey => ['use_parent_scope_value' => true],
                $encKey => ['use_parent_scope_value' => true],
                $userKey => ['use_parent_scope_value' => true],
                $passKey => ['use_parent_scope_value' => true]
            ]
        );
    }

    public function testConfigureSmtpConnectionConfigurationError(): void
    {
        $builder = $this->createFormBuilder();
        $builder->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_HOST), FormFieldType::class);
        $builder->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_PORT), FormFieldType::class);
        $builder->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_ENC), FormFieldType::class);
        $builder->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_USER), FormFieldType::class);
        $builder->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_PASS), FormFieldType::class);

        $error = $this->createMock(ConstraintViolationInterface::class);
        $error->expects($this->once())
            ->method('getMessage')
            ->willReturn('Test error');
        self::$validator->expects($this->once())
            ->method('validate')
            ->willReturn([$error]);

        $form = $builder->getForm();
        $form->submit([]);
        $this->assertCount(1, $form->getErrors());
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        /** @var ConfigSubscriber $subscriber */
        $subscriber = $this->getMockBuilder(ConfigSubscriber::class)
            ->setMethods(['__construct', 'preSubmit'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ContainerInterface $container */
        $container = $this->createMock(ContainerInterface::class);

        $formType = new FormType($subscriber, $container);

        return [
            new PreloadedExtension(
                [
                    FormType::class => $formType,
                ],
                [SymfonyFormType::class => [new DataBlockExtension()]]
            ),
        ];
    }

    /**
     * This method is used as configurator option.
     */
    public static function configure(FormBuilderInterface $builder, $options): void
    {
        $emailConfigurationConfigurator = new EmailConfigurationConfigurator(self::$encryptor, self::$validator);
        $emailConfigurationConfigurator->configure($builder, $options);
    }

    private function getConfiguratorOption(): string
    {
        return sprintf('%s::configure', EmailConfigurationConfiguratorTest::class);
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
                        'configurator' => $this->getConfiguratorOption()
                    ]
                ]
            ]
        );
    }

    private function getSmtpPasswordFieldKey(): string
    {
        return $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_PASS);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getConfigKey($name): string
    {
        return Configuration::getConfigKeyByName(
            $name,
            ConfigManager::SECTION_VIEW_SEPARATOR
        );
    }
}
