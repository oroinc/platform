<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\InstallerBundle\Form\Type\Configuration\DatabaseType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;

class DatabaseTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DatabaseTypeTest
     */
    private $type;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->type = new DatabaseType($this->translator);

        parent::setUp();
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, [], []);

        $this->assertTrue($form->has('oro_installer_database_driver'));
        $this->assertTrue($form->has('oro_installer_database_host'));
        $this->assertTrue($form->has('oro_installer_database_port'));
        $this->assertTrue($form->has('oro_installer_database_name'));
        $this->assertTrue($form->has('oro_installer_database_user'));
        $this->assertTrue($form->has('oro_installer_database_password'));
        $this->assertTrue($form->has('oro_installer_database_drop'));
        $this->assertTrue($form->has('oro_installer_database_driver_options'));
    }

    public function testFormViewDriverOptions()
    {
        $form = $this->factory->create($this->type, [
            'oro_installer_database_driver_options' => [
                'firstOption' => 'firstValue',
                'secondOption' => 'secondValue'
            ]
        ]);

        $formView = $form->createView();

        $expectedOptions = [
            0 => ['option_key' => 'firstOption', 'option_value' => 'firstValue'],
            1 => ['option_key' => 'secondOption', 'option_value' => 'secondValue'],
        ];

        $this->assertEquals(
            $expectedOptions,
            $formView->children['oro_installer_database_driver_options']->vars['value']
        );
    }

    public function testFormSubmit()
    {
        $form = $this->factory->create($this->type, []);

        $form->submit([
            'oro_installer_database_driver' => 'pdo_mysql',
            'oro_installer_database_host' => '127.0.0.1',
            'oro_installer_database_name' => 'dbname',
            'oro_installer_database_user' => 'user',
            'oro_installer_database_drop' => 'none',
            'oro_installer_database_driver_options' => [
                0 => ['option_key' => 'firstOption', 'option_value' => 'firstValue'],
                1 => ['option_key' => 'secondOption', 'option_value' => 'secondValue'],
            ]
        ]);

        $this->assertTrue($form->isValid());

        $expectedData = [
            'oro_installer_database_driver' => 'pdo_mysql',
            'oro_installer_database_host' => '127.0.0.1',
            'oro_installer_database_port' => null,
            'oro_installer_database_name' => 'dbname',
            'oro_installer_database_user' => 'user',
            'oro_installer_database_password' => null,
            'oro_installer_database_drop' => 'none',
            'oro_installer_database_driver_options' => [
                'firstOption' => 'firstValue',
                'secondOption' => 'secondValue'
            ]
        ];

        $this->assertEquals($expectedData, $form->getData());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_installer_configuration_database', $this->type->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(['oro_collection' => new CollectionType()], []),
            $this->getValidatorExtension(true),
        ];
    }
}
