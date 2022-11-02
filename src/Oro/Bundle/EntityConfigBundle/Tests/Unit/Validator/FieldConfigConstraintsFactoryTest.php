<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\Provider\StringFieldConfigConstraintsProvider;
use Oro\Bundle\EntityConfigBundle\Validator\FieldConfigConstraintsFactory;
use Symfony\Component\Validator\Constraints\Length;

class FieldConfigConstraintsFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const STRING_TYPE = 'string';

    private FieldConfigConstraintsFactory $constraintsFactory;

    protected function setUp(): void
    {
        $providers = new \ArrayIterator([
            self::STRING_TYPE => new StringFieldConfigConstraintsProvider(),
            'other_type' => new \stdClass(),
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
