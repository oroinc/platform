<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Form\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType as BaseIntegerType;

class IntegerTypeTest extends AbstractConfigTypeTestCase
{
    /** @var IntegerType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new IntegerType($this->typeHelper);
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
            BaseIntegerType::class,
            $this->type->getParent()
        );
    }
}
