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

class EmailConfigurationConfiguratorTest extends FormIntegrationTestCase
{
    private const ENCRYPTED_PASSWORD = 'encrypted_password';
    private const OLD_PASSWORD = 'old_password';
    private const NEW_PASSWORD = 'new_password';

    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private static $encryptor;

    protected function setUp()
    {
        parent::setUp();
        self::$encryptor = $this->createMock(SymmetricCrypterInterface::class);
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

        $expectedData = [
            $passwordKey => [
                'value' => self::OLD_PASSWORD
            ]
        ];

        self::assertArraySubset($expectedData, $form->getData());
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

        $expectedData = [
            $passwordKey => [
                'value' => self::ENCRYPTED_PASSWORD
            ]
        ];

        self::assertArraySubset($expectedData, $form->getData());
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
     *
     * @param FormBuilderInterface $builder
     * @param $options
     */
    public static function configure(FormBuilderInterface $builder, $options): void
    {
        $emailConfigurationConfigurator = new EmailConfigurationConfigurator(self::$encryptor);
        $emailConfigurationConfigurator->configure($builder, $options);
    }

    /**
     * @return string
     */
    private function getConfiguratorOption(): string
    {
        return sprintf('%s::configure', EmailConfigurationConfiguratorTest::class);
    }

    /**
     * @return FormBuilderInterface
     */
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

    /**
     * @return string
     */
    private function getSmtpPasswordFieldKey(): string
    {
        return Configuration::getConfigKeyByName(
            Configuration::KEY_SMTP_SETTINGS_PASS,
            ConfigManager::SECTION_VIEW_SEPARATOR
        );
    }
}
