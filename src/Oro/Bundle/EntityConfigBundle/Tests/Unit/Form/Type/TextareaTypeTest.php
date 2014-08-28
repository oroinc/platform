<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Form\Type\TextareaType;

class TextareaTypeTest extends AbstractConfigTypeTestCase
{
    /** @var TextareaType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new TextareaType($this->configManager);
    }

    /**
     * @dataProvider setDefaultOptionsProvider
     */
    public function testSetDefaultOptions(
        $configId,
        $hasConfig,
        $immutable,
        array $options,
        array $expectedOptions
    ) {
        $this->doTestSetDefaultOptions(
            $this->type,
            $configId,
            $hasConfig,
            $immutable,
            $options,
            $expectedOptions
        );
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_config_textarea',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'textarea',
            $this->type->getParent()
        );
    }
}
