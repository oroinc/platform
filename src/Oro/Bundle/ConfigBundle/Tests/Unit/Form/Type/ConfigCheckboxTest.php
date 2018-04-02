<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Form\Type\ConfigCheckbox;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ConfigCheckboxTest extends FormIntegrationTestCase
{
    /**
     * @var ConfigCheckbox
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new ConfigCheckbox();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_config_checkbox', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(CheckboxType::class, $this->formType->getParent());
    }

    /**
     * @param mixed $source
     * @param mixed $expected
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm($source, $expected)
    {
        $form = $this->factory->create(ConfigCheckbox::class);
        $form->setData($source);
        $this->assertEquals($expected, $form->getData());
    }

    /**
     * @return array
     */
    public function buildFormDataProvider()
    {
        return array(
            'valid true' => array(
                'source' => true,
                'expected' => true,
            ),
            'valid false' => array(
                'source' => false,
                'expected' => false,
            ),
            'empty string' => array(
                'source' => '',
                'expected' => false,
            ),
            'string 0' => array(
                'source' => '0',
                'expected' => false,
            ),
            'string 1' => array(
                'source' => '1',
                'expected' => true,
            ),
        );
    }
}
