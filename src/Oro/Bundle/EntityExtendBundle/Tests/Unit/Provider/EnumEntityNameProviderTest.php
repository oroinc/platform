<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityExtendBundle\Provider\EnumEntityNameProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestClass;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class EnumEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EnumEntityNameProvider */
    private $enumEntityNameProvider;

    protected function setUp(): void
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

    public function getNameProvider(): array
    {
        return [
            'full version of enum' => [
                [
                    EntityNameProviderInterface::FULL,
                    null,
                    new TestEnumValue('idValue', 'nameValue'),
                ],
                'nameValue',
            ],
            'short version of enum' => [
                [
                    EntityNameProviderInterface::SHORT,
                    null,
                    new TestEnumValue('idValue', 'nameValue'),
                ],
                'nameValue',
            ],
            'ful version of unsupported class' => [
                [
                    EntityNameProviderInterface::FULL,
                    null,
                    new TestClass(),
                ],
                false,
            ],
            'short version of unsupported class' => [
                [
                    EntityNameProviderInterface::SHORT,
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

    public function getNameDQLProvider(): array
    {
        return [
            'full version of enum' => [
                [
                    EntityNameProviderInterface::FULL,
                    null,
                    TestEnumValue::class,
                    't',
                ],
                't.name',
            ],
            'short version of enum' => [
                [
                    EntityNameProviderInterface::SHORT,
                    null,
                    TestEnumValue::class,
                    'e',
                ],
                'e.name',
            ],
            'ful version of unsupported class' => [
                [
                    EntityNameProviderInterface::FULL,
                    null,
                    TestClass::class,
                    't',
                ],
                false,
            ],
            'short version of unsupported class' => [
                [
                    EntityNameProviderInterface::SHORT,
                    null,
                    TestClass::class,
                    'e',
                ],
                false,
            ],
        ];
    }
}
