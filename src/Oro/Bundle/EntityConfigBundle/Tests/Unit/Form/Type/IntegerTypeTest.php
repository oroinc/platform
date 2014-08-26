<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Form\Type\IntegerType;

class IntegerTypeTest extends AbstractConfigTypeTestCase
{
    /** @var IntegerType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new IntegerType($this->configManager);
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
            'oro_entity_config_integer',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'integer',
            $this->type->getParent()
        );
    }
}
