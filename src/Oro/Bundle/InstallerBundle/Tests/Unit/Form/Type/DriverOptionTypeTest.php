<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Form\Type;

use Oro\Bundle\InstallerBundle\Form\Type\Configuration\DriverOptionType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class DriverOptionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DriverOptionType
     */
    private $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->type = new DriverOptionType();

        parent::setUp();
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, null, []);
        $this->assertTrue($form->has('option_key'));
        $this->assertTrue($form->has('option_value'));
    }

    public function testFormSubmit()
    {
        $form = $this->factory->create($this->type, null, []);

        $data = ['option_key'=> 'someKey', 'option_value' => 'someValue'];
        $form->submit($data);

        $this->assertTrue($form->isValid());
        $this->assertEquals($data, $form->getData());
    }

    public function testFormView()
    {
        $optionKey = 'someKey';
        $optionValue = 'someValue';
        $form = $this->factory->create($this->type, ['option_key' => $optionKey, 'option_value' => $optionValue]);
        $formView = $form->createView();

        $this->assertEquals($optionKey, $formView->children['option_key']->vars['value']);
        $this->assertEquals($optionValue, $formView->children['option_value']->vars['value']);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_installer_configuration_driver_option', $this->type->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([], []),
            $this->getValidatorExtension(true),
        ];
    }
}
