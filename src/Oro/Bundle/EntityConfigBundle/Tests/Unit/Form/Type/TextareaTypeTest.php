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
     * @dataProvider buildViewProvider
     */
    public function testBuildView($configId, $hasConfig, $immutable, $expectedViewVars)
    {
        $this->doTestBuildView($this->type, $configId, $hasConfig, $immutable, $expectedViewVars);
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
