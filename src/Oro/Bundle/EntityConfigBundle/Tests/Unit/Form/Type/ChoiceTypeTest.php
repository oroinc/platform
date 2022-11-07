<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as BaseChoiceType;

class ChoiceTypeTest extends AbstractConfigTypeTestCase
{
    /** @var ChoiceType */
    private $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ChoiceType($this->typeHelper);
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
            BaseChoiceType::class,
            $this->type->getParent()
        );
    }
}
