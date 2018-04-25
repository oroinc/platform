<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Form\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as BaseTextareaType;

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

    public function testGetParent()
    {
        $this->assertEquals(
            BaseTextareaType::class,
            $this->type->getParent()
        );
    }
}
