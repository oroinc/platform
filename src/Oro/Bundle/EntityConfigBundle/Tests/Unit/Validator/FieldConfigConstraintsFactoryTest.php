<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\Provider\StringFieldConfigConstraintsProvider;
use Oro\Bundle\EntityConfigBundle\Validator\FieldConfigConstraintsFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Constraints\Length;

class FieldConfigConstraintsFactoryTest extends TestCase
{
    private const STRING_TYPE = 'string';

    private FieldConfigConstraintsFactory $constraintsFactory;

    #[\Override]
    protected function setUp(): void
    {
        $providers = new ServiceLocator([
            self::STRING_TYPE => function () {
                return new StringFieldConfigConstraintsProvider();
            },
            'other_type' => function () {
                return new \stdClass();
            }
        ]);

        $this->constraintsFactory = new FieldConfigConstraintsFactory($providers);
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(
        string $fieldType,
        array $options,
        bool $isEmptyConstraints,
        string $constraintType
    ): void {
        $configId = new FieldConfigId('test', \stdClass::class, 'field', $fieldType);
        $config = new Config($configId, $options);

        $constraints = $this->constraintsFactory->create($config);

        self::assertEquals($isEmptyConstraints, empty($constraints));

        if (!$isEmptyConstraints) {
            $constraint = reset($constraints);
            self::assertInstanceOf($constraintType, $constraint);
        }
    }

    public function createProvider(): array
    {
        return [
            [self::STRING_TYPE, ['length' => 20], false, Length::class],
            ['other_type', [], true, 'OtherType'],
            ['my_type', [], true, 'MyType'],
        ];
    }
}
