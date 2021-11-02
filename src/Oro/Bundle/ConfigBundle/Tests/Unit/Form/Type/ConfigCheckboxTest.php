<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Form\Type\ConfigCheckbox;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ConfigCheckboxTest extends FormIntegrationTestCase
{
    /** @var ConfigCheckbox */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new ConfigCheckbox();
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
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(string|bool $source, bool $expected)
    {
        $form = $this->factory->create(ConfigCheckbox::class);
        $form->setData($source);
        $this->assertSame($expected, $form->getData());
    }

    public function buildFormDataProvider(): array
    {
        return [
            'valid true' => [
                'source' => true,
                'expected' => true,
            ],
            'valid false' => [
                'source' => false,
                'expected' => false,
            ],
            'empty string' => [
                'source' => '',
                'expected' => false,
            ],
            'string 0' => [
                'source' => '0',
                'expected' => false,
            ],
            'string 1' => [
                'source' => '1',
                'expected' => true,
            ],
        ];
    }
}
