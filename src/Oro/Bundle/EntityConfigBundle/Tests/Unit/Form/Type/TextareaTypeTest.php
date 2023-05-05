<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Form\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType as BaseTextareaType;

class TextareaTypeTest extends AbstractConfigTypeTestCase
{
    /** @var TextareaType */
    private $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new TextareaType($this->typeHelper);
    }

    /**
     * @dataProvider configureOptionsProvider
     */
    public function testConfigureOptions(
        ConfigIdInterface $configId,
        bool $immutable,
        array $options,
        array $expectedOptions
    ) {
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
