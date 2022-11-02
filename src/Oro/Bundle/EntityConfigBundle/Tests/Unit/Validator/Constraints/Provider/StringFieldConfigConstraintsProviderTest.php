<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator\Constraints\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\Provider\StringFieldConfigConstraintsProvider;
use Symfony\Component\Validator\Constraints\Length;

class StringFieldConfigConstraintsProviderTest extends \PHPUnit\Framework\TestCase
{
    private FieldConfigId $configId;

    protected function setUp(): void
    {
        $this->configId = new FieldConfigId('test', \stdClass::class, 'field');
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(?int $length, int $expectedLength): void
    {
        $config = new Config($this->configId, ['length' => $length]);
        $constraintsProvider = new StringFieldConfigConstraintsProvider();
        $constraints = $constraintsProvider->create($config);
        $constraint = reset($constraints);

        self::assertInstanceOf(Length::class, $constraint);
        self::assertEquals($expectedLength, $constraint->max);
    }

    public function createProvider(): array
    {
        return [
            [null, 255],
            [10, 10],
        ];
    }
}
