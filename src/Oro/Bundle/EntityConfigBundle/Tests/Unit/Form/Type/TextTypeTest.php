<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Form\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextType as BaseTextType;

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

    public function testGetParent()
    {
        $this->assertEquals(
            BaseTextType::class,
            $this->type->getParent()
        );
    }
}
