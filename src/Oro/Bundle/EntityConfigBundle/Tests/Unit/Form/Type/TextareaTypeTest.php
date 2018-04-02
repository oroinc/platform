<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Form\Type\TextareaType;

class TextareaTypeTest extends AbstractConfigTypeTestCase
{
    /** @var TextareaType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new TextareaType($this->typeHelper);
    }

    /**
     * @dataProvider configureOptionsProvider
     * @param ConfigIdInterface $configId
     * @param boolean $immutable
     * @param array $options
     * @param array $expectedOptions
     */
    public function testConfigureOptions($configId, $immutable, array $options, array $expectedOptions)
    {
        $this->doTestConfigureOptions($this->type, $configId, $immutable, $options, $expectedOptions);
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
