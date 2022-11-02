<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Provider\DecimalFieldConfigConstraintsProvider;

class DecimalFieldConfigConstraintsProviderTest extends \PHPUnit\Framework\TestCase
{
    private FieldConfigId $configId;

    private DecimalFieldConfigConstraintsProvider $provider;

    protected function setUp(): void
    {
        $this->configId = new FieldConfigId('test', \stdClass::class, 'field');

        $this->provider = new DecimalFieldConfigConstraintsProvider();
    }

    /**
     * @dataProvider createNoConstraintsProvider
     */
    public function testCreateNoConstraints(array $options): void
    {
        $config = new Config($this->configId, $options);

        self::assertEmpty($this->provider->create($config));
    }

    public function createNoConstraintsProvider(): array
    {
        return [
            [[]],
            [['precision' => 6]],
            [['scale' => 6]],
        ];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(array $options, array $expectedOptions): void
    {
        $config = new Config($this->configId, $options);
        $constraints = $this->provider->create($config);
        $constraint = reset($constraints);

        self::assertEquals(new Decimal($expectedOptions), $constraint);
    }

    public function createProvider(): array
    {
        return [
            'default options' => [
                ['precision' => null, 'scale' => null],
                ['precision' => 10, 'scale' => 0],
            ],
            'custom options' => [
                ['precision' => 6, 'scale' => 2],
                ['precision' => 6, 'scale' => 2],
            ],
        ];
    }
}
