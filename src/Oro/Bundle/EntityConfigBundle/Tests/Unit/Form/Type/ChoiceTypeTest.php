<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Form\Type\ChoiceType;

class ChoiceTypeTest extends AbstractConfigTypeTestCase
{
    /** @var ChoiceType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new ChoiceType($this->configManager);
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
            'oro_entity_config_choice',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'choice',
            $this->type->getParent()
        );
    }
}
