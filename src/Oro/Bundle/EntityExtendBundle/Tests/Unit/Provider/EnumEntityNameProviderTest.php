<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityExtendBundle\Provider\EnumEntityNameProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class EnumEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EnumEntityNameProvider
     */
    protected $enumEntityNameProvider;

    public function setUp()
    {
        $this->enumEntityNameProvider = new EnumEntityNameProvider();
    }

    /**
     * @dataProvider getNameProvider
     */
    public function testGetName(array $args, $expectedValue)
    {
        $this->assertSame(
            $expectedValue,
            call_user_func_array([$this->enumEntityNameProvider, 'getName'], $args)
        );
    }

    public function getNameProvider()
    {
        return [
            'full version of enum' => [
                [
                    EnumEntityNameProvider::FULL,
                    null,
                    new TestEnumValue('idValue', 'nameValue'),
                ],
                'nameValue',
            ],
            'short version of enum' => [
                [
                    EnumEntityNameProvider::SHORT,
                    null,
                    new TestEnumValue('idValue', 'nameValue'),
                ],
                'nameValue',
            ],
            'ful version of unsupported class' => [
                [
                    EnumEntityNameProvider::FULL,
                    null,
                    new TestClass(),
                ],
                false,
            ],
            'short version of unsupported class' => [
                [
                    EnumEntityNameProvider::SHORT,
                    null,
                    new TestClass(),
                ],
                false,
            ],
        ];
    }

    /**
     * @dataProvider getNameDQLProvider
     */
    public function testGetNameDQL(array $args, $expectedValue)
    {
        $this->assertSame(
            $expectedValue,
            call_user_func_array([$this->enumEntityNameProvider, 'getNameDQL'], $args)
        );
    }

    public function getNameDQLProvider()
    {
        return [
            'full version of enum' => [
                [
                    EnumEntityNameProvider::FULL,
                    null,
                    TestEnumValue::class,
                    't',
                ],
                't.name',
            ],
            'short version of enum' => [
                [
                    EnumEntityNameProvider::SHORT,
                    null,
                    TestEnumValue::class,
                    'e',
                ],
                'e.name',
            ],
            'ful version of unsupported class' => [
                [
                    EnumEntityNameProvider::FULL,
                    null,
                    TestClass::class,
                    't',
                ],
                false,
            ],
            'short version of unsupported class' => [
                [
                    EnumEntityNameProvider::SHORT,
                    null,
                    TestClass::class,
                    'e',
                ],
                false,
            ],
        ];
    }
}
