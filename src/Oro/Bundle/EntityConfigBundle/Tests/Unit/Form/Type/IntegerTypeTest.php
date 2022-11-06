<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Form\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType as BaseIntegerType;

class IntegerTypeTest extends AbstractConfigTypeTestCase
{
    /** @var IntegerType */
    private $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new IntegerType($this->typeHelper);
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
            BaseIntegerType::class,
            $this->type->getParent()
        );
    }
}
