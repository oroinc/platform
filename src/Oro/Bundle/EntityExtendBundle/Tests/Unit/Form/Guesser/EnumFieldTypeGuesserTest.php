<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Guesser;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Guesser\EnumFieldTypeGuesser;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Guess\TypeGuess;

class EnumFieldTypeGuesserTest extends TestCase
{
    private const CLASS_NAME = 'Test\Entity';
    private const PROPERTY_NAME = 'testProperty';
    private const ENUM_CODE = 'test_enum_code';

    private ManagerRegistry&MockObject $managerRegistry;
    private ConfigProvider&MockObject $entityConfigProvider;
    private ConfigProvider&MockObject $formConfigProvider;
    private ConfigManager&MockObject $configManager;
    private ConfigProvider&MockObject $enumConfigProvider;
    private ExtendFieldFormOptionsProviderInterface&MockObject $formOptionsProvider;
    private EnumFieldTypeGuesser $guesser;

    #[\Override]
    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->enumConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formOptionsProvider = $this->createMock(ExtendFieldFormOptionsProviderInterface::class);

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfigManager')
            ->willReturn($this->configManager);

        $this->guesser = new EnumFieldTypeGuesser(
            $this->managerRegistry,
            $this->entityConfigProvider,
            $this->formConfigProvider,
            $this->formOptionsProvider
        );
    }

    public function testGuessTypeWhenEnumConfigProviderNotExists(): void
    {
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn(null);

        $result = $this->guesser->guessType(self::CLASS_NAME, self::PROPERTY_NAME);

        $this->assertNull($result);
    }

    public function testGuessTypeWhenEnumConfigProviderHasNoConfig(): void
    {
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($this->enumConfigProvider);

        $this->enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn(false);

        $result = $this->guesser->guessType(self::CLASS_NAME, self::PROPERTY_NAME);

        $this->assertNull($result);
    }

    public function testGuessTypeWhenFieldTypeIsNotEnum(): void
    {
        $fieldConfigId = new FieldConfigId('enum', self::CLASS_NAME, self::PROPERTY_NAME, 'string');
        $enumFieldConfig = new Config($fieldConfigId);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($this->enumConfigProvider);

        $this->enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn(true);

        $this->enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn($enumFieldConfig);

        $result = $this->guesser->guessType(self::CLASS_NAME, self::PROPERTY_NAME);

        $this->assertNull($result);
    }

    public function testGuessTypeWhenEnumCodeIsEmpty(): void
    {
        $fieldConfigId = new FieldConfigId('enum', self::CLASS_NAME, self::PROPERTY_NAME, 'enum');
        $enumFieldConfig = new Config($fieldConfigId, ['enum_code' => null]);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($this->enumConfigProvider);

        $this->enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn(true);

        $this->enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn($enumFieldConfig);

        $result = $this->guesser->guessType(self::CLASS_NAME, self::PROPERTY_NAME);

        $this->assertNull($result);
    }

    public function testGuessTypeReturnsEnumSelectType(): void
    {
        $fieldConfigId = new FieldConfigId('enum', self::CLASS_NAME, self::PROPERTY_NAME, 'enum');
        $enumFieldConfig = new Config($fieldConfigId, ['enum_code' => self::ENUM_CODE]);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($this->enumConfigProvider);

        $this->enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn(true);

        $this->enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn($enumFieldConfig);

        $expectedOptions = [
            'enum_code' => self::ENUM_CODE,
            'label' => 'Test Label',
            'block' => 'general',
            'multiple' => false,
        ];

        $this->formOptionsProvider->expects($this->once())
            ->method('getOptions')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn($expectedOptions);

        $result = $this->guesser->guessType(self::CLASS_NAME, self::PROPERTY_NAME);

        $this->assertInstanceOf(TypeGuess::class, $result);
        $this->assertEquals(EnumSelectType::class, $result->getType());
        $this->assertEquals($expectedOptions, $result->getOptions());
        $this->assertEquals(TypeGuess::HIGH_CONFIDENCE, $result->getConfidence());
    }

    public function testGuessTypeReturnsEnumSelectTypeWithLabel(): void
    {
        $label = 'Test Label';
        $fieldConfigId = new FieldConfigId('enum', self::CLASS_NAME, self::PROPERTY_NAME, 'enum');
        $enumFieldConfig = new Config($fieldConfigId, ['enum_code' => self::ENUM_CODE]);

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($this->enumConfigProvider);

        $this->enumConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn(true);

        $this->enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn($enumFieldConfig);

        $expectedOptions = [
            'enum_code' => self::ENUM_CODE,
            'label' => $label,
            'block' => 'general',
            'multiple' => false,
        ];

        $this->formOptionsProvider->expects($this->once())
            ->method('getOptions')
            ->with(self::CLASS_NAME, self::PROPERTY_NAME)
            ->willReturn($expectedOptions);

        $result = $this->guesser->guessType(self::CLASS_NAME, self::PROPERTY_NAME);

        $this->assertInstanceOf(TypeGuess::class, $result);
        $this->assertEquals(EnumSelectType::class, $result->getType());
        $this->assertEquals($expectedOptions, $result->getOptions());
        $this->assertEquals(TypeGuess::HIGH_CONFIDENCE, $result->getConfidence());
    }
}
