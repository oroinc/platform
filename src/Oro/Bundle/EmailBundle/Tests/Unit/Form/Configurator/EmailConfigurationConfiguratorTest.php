<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Configurator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\EventListener\ConfigSubscriber;
use Oro\Bundle\ConfigBundle\Form\Type\FormFieldType;
use Oro\Bundle\ConfigBundle\Form\Type\FormType;
use Oro\Bundle\ConfigBundle\Form\Type\ParentScopeCheckbox;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
use Oro\Bundle\EmailBundle\Form\Configurator\EmailConfigurationConfigurator;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType as SymfonyFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailConfigurationConfiguratorTest extends FormIntegrationTestCase
{
    const ENCRYPTED_PASSWORD = 'encrypted_password';
    const OLD_PASSWORD = 'old_password';
    const NEW_PASSWORD = 'new_password';

    /**
     * @var Mcrypt|\PHPUnit_Framework_MockObject_MockObject
     */
    private static $encryptor;

    /**
     * @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private static $validator;

    protected function setUp()
    {
        parent::setUp();
        self::$encryptor = $this->createMock(Mcrypt::class);
        self::$validator = $this->createMock(ValidatorInterface::class);
    }

    public function testConfigureWithParentScopeValue(): void
    {
        $builder = $this->createFormBuilder();

        self::$validator->expects($this->never())
            ->method('validate');

        $builder->getForm()->submit(
            [
                $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_HOST) => ['use_parent_scope_value' => true],
                $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_PORT) => ['use_parent_scope_value' => true],
                $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_ENC) => ['use_parent_scope_value' => true],
                $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_USER) => ['use_parent_scope_value' => true],
                $this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_PASS) => ['use_parent_scope_value' => true]
            ]
        );
    }

    public function testConfigureSmtpConnectionConfigurationError(): void
    {
        $builder = $this->createFormBuilder();

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
                    ParentScopeCheckbox::class => new ParentScopeCheckbox()
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
        $emailConfigurationConfigurator->setValidator(self::$validator);
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
        )
        ->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_HOST), FormFieldType::class)
        ->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_PORT), FormFieldType::class)
        ->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_ENC), FormFieldType::class)
        ->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_USER), FormFieldType::class)
        ->add($this->getConfigKey(Configuration::KEY_SMTP_SETTINGS_PASS), FormFieldType::class);
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
