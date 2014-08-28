<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Form\Type\TextType;

class TextTypeTest extends AbstractConfigTypeTestCase
{
    /** @var TextType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new TextType($this->typeHelper);
    }

    /**
     * @dataProvider setDefaultOptionsProvider
     */
    public function testSetDefaultOptions($configId, $immutable, array $options, array $expectedOptions)
    {
        $this->doTestSetDefaultOptions($this->type, $configId, $immutable, $options, $expectedOptions);
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_config_text',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'text',
            $this->type->getParent()
        );
    }
}
