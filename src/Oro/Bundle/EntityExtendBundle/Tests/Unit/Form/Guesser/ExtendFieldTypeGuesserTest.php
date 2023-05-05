<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Guesser;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Form\Guesser\ExtendFieldTypeGuesser;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsProviderInterface;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormTypeProvider;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\TypeGuess;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendFieldTypeGuesserTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'Oro\Bundle\SomeBundle\Entity\SomeClassName';
    private const CLASS_PROPERTY = 'SomeClassProperty';
    private const PROPERTY_TYPE = 'bigint';
    private const SOME_LABEL = 'someLabel';

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $formConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ExtendFieldFormTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendFieldFormTypeProvider;

    /** @var ExtendFieldFormOptionsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $extendFieldFormOptionsProvider;

    /** @var ExtendFieldTypeGuesser|\PHPUnit\Framework\MockObject\MockObject */
    private $guesser;

    protected function setUp(): void
    {
        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendFieldFormTypeProvider = new ExtendFieldFormTypeProvider();
        $this->extendFieldFormOptionsProvider = $this->createMock(ExtendFieldFormOptionsProviderInterface::class);

        $this->guesser = new ExtendFieldTypeGuesser(
            $this->createMock(ManagerRegistry::class),
            $entityConfigProvider,
            $this->formConfigProvider,
            $this->extendConfigProvider,
            $this->extendFieldFormTypeProvider,
            $this->extendFieldFormOptionsProvider
        );
    }

    private function expectsHasExtendConfig(bool $hasConfig): void
    {
        $this->extendConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with(self::CLASS_NAME, self::CLASS_PROPERTY)
            ->willReturn($hasConfig);
    }

    private function createFieldConfig(string $scopeName, array $scopeOptions, string $fieldType): Config
    {
        return new Config(
            new FieldConfigId($scopeName, self::CLASS_NAME, self::CLASS_PROPERTY, $fieldType),
            $scopeOptions
        );
    }

    private function createConfigProviderExpectation(
        ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider,
        string $fieldType,
        string $scopeName,
        array $scopeOptions
    ): void {
        $config = $this->createFieldConfig($scopeName, $scopeOptions, $fieldType);

        $configProvider->expects(self::once())
            ->method('getConfig')
            ->with(self::CLASS_NAME, self::CLASS_PROPERTY)
            ->willReturn($config);
    }

    private function expectsGetFormConfig(array $scopeOptions, string $fieldType = self::PROPERTY_TYPE): void
    {
        $this->createConfigProviderExpectation($this->formConfigProvider, $fieldType, 'form', $scopeOptions);
    }

    private function expectsGetExtendConfig(array $scopeOptions, string $fieldType = self::PROPERTY_TYPE): void
    {
        $this->createConfigProviderExpectation($this->extendConfigProvider, $fieldType, 'extend', $scopeOptions);
    }

    private function assertIsDefaultTypeGuess(TypeGuess $typeGuess): void
    {
        $defaultTypeGuess = new TypeGuess(TextType::class, [], TypeGuess::LOW_CONFIDENCE);
        self::assertEquals($defaultTypeGuess, $typeGuess);
    }

    private function assertTypeGuess(TypeGuess $typeGuess, array $options = [], string $type = 'text'): void
    {
        $defaultTypeGuess = new TypeGuess($type, $options, TypeGuess::HIGH_CONFIDENCE);
        self::assertEquals($defaultTypeGuess, $typeGuess);
    }

    public function testGuessTypeWhenNoExtendConfigExists(): void
    {
        $this->expectsHasExtendConfig(false);

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    public function testGuessTypeWhenExtendConfigExistsAndFormConfigNotEnabled(): void
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => false]);

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    public function testGuessTypeWhenFormScopeHasTypeButNotApplicable(): void
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig([
            'is_enabled' => true,
            'type' => 'text',
        ]);

        $this->expectsGetExtendConfig([]);

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    public function testGuessTypeWhenFormScopeHasTypeAndFieldIsApplicable(): void
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig([
            'is_enabled' => true,
            'type' => 'text',
        ]);

        $this->expectsGetExtendConfig(['owner' => ExtendScope::OWNER_CUSTOM]);

        $options = [
            'label' => self::SOME_LABEL,
            'required' => false,
            'block' => 'general',
        ];
        $this->extendFieldFormOptionsProvider->expects(self::once())
            ->method('getOptions')
            ->with(self::CLASS_NAME, self::CLASS_PROPERTY)
            ->willReturn($options);

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertTypeGuess($typeGuess, $options);
    }

    public function testGuessTypeWhenFormScopeHasNoTypeAndTypeMapNotExists(): void
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => true]);

        $this->expectsGetExtendConfig(['owner' => ExtendScope::OWNER_CUSTOM]);

        $this->extendFieldFormOptionsProvider->expects(self::never())
            ->method('getOptions');

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    /**
     * @dataProvider notApplicableRegularFieldDataProvider
     */
    public function testGuessTypeWhenFormScopeHasNoTypeAndTypeMapExistsButNotApplicableForRegularField(
        string $fieldType,
        array $extendConfig
    ): void {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => true], $fieldType);

        $this->expectsGetExtendConfig($extendConfig, $fieldType);

        $this->extendFieldFormOptionsProvider->expects(self::never())
            ->method('getOptions');

        $this->extendFieldFormTypeProvider->addExtendTypeMapping($fieldType, 'text');
        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    public function notApplicableRegularFieldDataProvider(): array
    {
        return [
            'empty extend config' => ['fieldType' => self::PROPERTY_TYPE, 'extendConfig' => []],
            'owner is not custom' => [
                'fieldType' => self::PROPERTY_TYPE,
                'extendConfig' => ['owner' => ExtendScope::OWNER_SYSTEM],
            ],
            'field is deleted' => [
                'fieldType' => self::PROPERTY_TYPE,
                'extendConfig' => ['owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => true, 'is_deleted' => true],
            ],
            'but state is new' => [
                'fieldType' => self::PROPERTY_TYPE,
                'extendConfig' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'is_extend' => true,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_NEW,
                ],
            ],
            'state is deleted' => [
                'fieldType' => self::PROPERTY_TYPE,
                'extendConfig' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'is_extend' => true,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_DELETE,
                ],
            ],
            'fieldType is TO_ONE' => [
                'fieldType' => RelationType::TO_ONE,
                'extendConfig' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'is_extend' => true,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_ACTIVE,
                ],
            ],
        ];
    }

    /**
     * @dataProvider notApplicableRelationDataProvider
     */
    public function testGuessTypeWhenFormScopeHasNoTypeAndTypeMapExistsButNotApplicableForRelation(
        string $fieldType,
        array $fileExtendScopeOptions
    ): void {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => true], $fieldType);

        $extendScopeOptions = [
            'owner' => ExtendScope::OWNER_CUSTOM,
            'is_extend' => true,
            'is_deleted' => false,
            'state' => ExtendScope::STATE_ACTIVE,
            'target_entity' => File::class,
        ];
        $extendFieldConfig = $this->createFieldConfig('extend', $extendScopeOptions, $fieldType);
        $fileExtendConfig = $this->createFieldConfig('extend', $fileExtendScopeOptions, $fieldType);

        $this->extendConfigProvider->expects(self::exactly(2))
            ->method('getConfig')
            ->withConsecutive([self::CLASS_NAME, self::CLASS_PROPERTY], [File::class])
            ->willReturn($extendFieldConfig, $fileExtendConfig);

        $this->extendFieldFormOptionsProvider->expects(self::never())
            ->method('getOptions');

        $this->extendFieldFormTypeProvider->addExtendTypeMapping($fieldType, 'text');
        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    public function notApplicableRelationDataProvider(): array
    {
        return [
            'has target_entity and is deleted' => [
                'fieldType' => 'file',
                'fileExtendScopeOptions' => [
                    'is_extend' => true,
                    'is_deleted' => true,
                ],
            ],
            'has target_entity and state is new' => [
                'fieldType' => 'file',
                'fileExtendScopeOptions' => [
                    'is_extend' => true,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_NEW,

                ],
            ],
            'has target_entity and state is deleted' => [
                'fieldType' => 'file',
                'fileExtendScopeOptions' => [
                    'is_extend' => true,
                    'is_deleted' => false,
                    'state' => ExtendScope::STATE_DELETE,

                ],
            ],
        ];
    }

    public function guessTypeDataProvider(): array
    {
        return [
            'regular field' => [
                'extendConfig' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                ],
            ],
            'regular extend field' => [
                'extendConfig' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'is_extend' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider guessTypeDataProvider
     */
    public function testGuessTypeWhenFormScopeHasNoTypeAndTypeMapExistsAndFieldIsApplicable(array $extendConfig): void
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => true]);

        $this->expectsGetExtendConfig($extendConfig);

        $options = [
            'label' => self::SOME_LABEL,
            'required' => false,
            'block' => 'general',
        ];
        $this->extendFieldFormOptionsProvider->expects(self::once())
            ->method('getOptions')
            ->with(self::CLASS_NAME, self::CLASS_PROPERTY)
            ->willReturn($options);

        $this->extendFieldFormTypeProvider->addExtendTypeMapping(self::PROPERTY_TYPE, 'customType');
        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertTypeGuess($typeGuess, $options, 'customType');
    }
}
