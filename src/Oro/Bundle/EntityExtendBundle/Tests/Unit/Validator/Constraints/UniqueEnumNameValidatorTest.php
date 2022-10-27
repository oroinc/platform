<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueEnumName;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueEnumNameValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueEnumNameValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new UniqueEnumNameValidator($this->configManager);
    }

    public function testValidateForEmptyEnumName()
    {
        $entityClassName = 'Test\Entity1';
        $fieldName = 'field1';

        $constraint = new UniqueEnumName(
            [
                'entityClassName' => $entityClassName,
                'fieldName'       => $fieldName,
            ]
        );

        $this->validator->validate('', $constraint);

        $this->assertNoViolation();
    }

    public function testValidateForInvalidEnumName()
    {
        $entityClassName = 'Test\Entity1';
        $fieldName = 'field1';

        $constraint = new UniqueEnumName(
            [
                'entityClassName' => $entityClassName,
                'fieldName'       => $fieldName,
            ]
        );

        $this->validator->validate('!@#$', $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $enumName, string $entityClassName, string $fieldName, bool $violation)
    {
        $config1 = new Config(new EntityConfigId('extend', 'Test\Entity1'));
        $config1->set('is_extend', true);
        $config1->set('state', ExtendScope::STATE_NEW);
        $config2 = new Config(new EntityConfigId('extend', 'Test\Entity2'));
        $config2->set('is_extend', true);
        $config2->set('state', ExtendScope::STATE_UPDATE);
        $config3 = new Config(new EntityConfigId('extend', 'Test\Entity3'));
        $config3->set('is_extend', true);
        $config3->set('state', ExtendScope::STATE_DELETE);
        $config4 = new Config(new EntityConfigId('extend', 'Test\Entity4'));
        $config4->set('is_extend', true);
        $config4->set('state', ExtendScope::STATE_ACTIVE);
        $config5 = new Config(new EntityConfigId('extend', 'Test\Entity5'));
        $config5->set('state', ExtendScope::STATE_NEW);

        $configs = [$config1, $config2, $config3, $config4, $config5];

        $fieldConfig11 = new Config(new FieldConfigId('extend', 'Test\Entity1', 'field1', 'enum'));
        $fieldConfig11->set('state', ExtendScope::STATE_NEW);
        $enumFieldConfigs11 = new Config(new FieldConfigId('enum', 'Test\Entity1', 'field1', 'enum'));
        $enumFieldConfigs11->set('enum_name', 'Enum 1');

        $fieldConfigs1 = [$fieldConfig11];

        $fieldConfig21 = new Config(new FieldConfigId('extend', 'Test\Entity2', 'field1', 'multiEnum'));
        $fieldConfig21->set('state', ExtendScope::STATE_NEW);
        $enumFieldConfigs21 = new Config(new FieldConfigId('enum', 'Test\Entity2', 'field1', 'multiEnum'));
        $enumFieldConfigs21->set('enum_name', 'Enum 2');

        $fieldConfigs2 = [$fieldConfig21];

        $enumValueConfig = new Config(new EntityConfigId('extend', 'Test\EnumValue'));
        $enumValueConfig->set('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS);
        $enumValueEnumConfig = new Config(new EntityConfigId('enum', 'Test\EnumValue'));
        $enumValueEnumConfig->set('code', 'existing_enum');

        $enumConfigProvider = $this->createMock(ConfigProvider::class);
        $extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $extendConfigProvider],
                ['enum', $enumConfigProvider]
            ]);
        $extendConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->willReturnMap([
                [null, false, $configs],
                [null, true, array_merge([$enumValueConfig], $configs)],
                ['Test\Entity1', false, $fieldConfigs1],
                ['Test\Entity2', false, $fieldConfigs2],
            ]);
        $enumConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                ['Test\EnumValue', null, $enumValueEnumConfig],
                ['Test\Entity1', 'field1', $enumFieldConfigs11],
                ['Test\Entity2', 'field1', $enumFieldConfigs21],
            ]);

        $constraint = new UniqueEnumName(
            [
                'entityClassName' => $entityClassName,
                'fieldName'       => $fieldName,
            ]
        );

        $this->validator->validate($enumName, $constraint);

        if ($violation) {
            $this->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $enumName)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
    }

    public function validateDataProvider(): array
    {
        return [
            ['Existing Enum', 'Test\Entity1', 'field1', true],
            ['Enum 1', 'Test\Entity1', 'field1', false],
            ['Enum 2', 'Test\Entity1', 'field1', true],
            ['Enum 3', 'Test\Entity1', 'field1', false],
        ];
    }
}
