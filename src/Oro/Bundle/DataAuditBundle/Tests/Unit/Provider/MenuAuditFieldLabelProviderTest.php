<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\DataAuditBundle\Provider\MenuAuditFieldLabelProvider;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditLevelProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuAuditFieldLabelProviderTest extends TestCase
{
    private const string GLOBAL_LEVEL = 'Oro\Bundle\NavigationBundle\GlobalBackOfficeMenu';
    private const string USER_LEVEL = 'Oro\Bundle\NavigationBundle\UserBackOfficeMenu';

    private MenuAuditFieldLabelProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $levelProvider = $this->createMock(MenuAuditLevelProvider::class);
        $levelProvider->expects(self::any())
            ->method('isType')
            ->willReturnCallback(
                static fn (?string $objectClass): bool => \in_array(
                    $objectClass,
                    [self::GLOBAL_LEVEL, self::USER_LEVEL],
                    true
                )
            );
        $levelProvider->expects(self::any())
            ->method('all')
            ->willReturn([self::GLOBAL_LEVEL => 'global', self::USER_LEVEL => 'user']);

        $entityConfigManager = $this->createMock(ConfigManager::class);
        $entityConfigManager->expects(self::any())
            ->method('getConfigs')
            ->with('entity', MenuUpdate::class)
            ->willReturn([
                $this->createFieldConfig('uri', 'oro.navigation.menuupdate.uri.label'),
                $this->createFieldConfig('titles', 'oro.navigation.menuupdate.titles.label'),
                $this->createFieldConfig('parentKey', 'oro.navigation.menuupdate.parent_key.label'),
                $this->createFieldConfig('image', ''),
                $this->createEntityConfig(),
            ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'oro.navigation.menuupdate.uri.label' => 'URI',
                'oro.navigation.menuupdate.titles.label' => 'Title',
                'oro.navigation.menuupdate.parent_key.label' => 'Parent Key',
                'oro.dataaudit.menu.field.parentKey' => 'Parent',
                default => $key,
            });

        $this->provider = new MenuAuditFieldLabelProvider(
            $levelProvider,
            $entityConfigManager,
            MenuUpdate::class,
            $translator
        );
    }

    public function testNamesAPropertyTheWayTheMenuFormNamesIt(): void
    {
        self::assertSame('URI', $this->provider->getLabel(self::GLOBAL_LEVEL, 'uri'));
    }

    public function testATranslationRenamesAPropertyInTheAudit(): void
    {
        self::assertSame('Parent', $this->provider->getLabel(self::GLOBAL_LEVEL, 'parentKey'));
    }

    public function testALocalizedValueIsNamedAfterItsLocalization(): void
    {
        self::assertSame('Title (German)', $this->provider->getLabel(self::GLOBAL_LEVEL, 'titles|German'));
        self::assertSame('Title', $this->provider->getLabel(self::GLOBAL_LEVEL, 'titles'));
    }

    public function testAPropertyWithoutALabelIsNamedByItself(): void
    {
        self::assertSame('image', $this->provider->getLabel(self::GLOBAL_LEVEL, 'image'));
        self::assertSame('synthetic', $this->provider->getLabel(self::GLOBAL_LEVEL, 'synthetic'));
    }

    public function testNamesNothingOfAnotherKind(): void
    {
        self::assertNull($this->provider->getLabel('Oro\Bundle\CommerceMenuBundle\GlobalStorefrontMenu', 'uri'));
        self::assertNull($this->provider->getLabel(null, 'uri'));
    }

    public function testMatchesPropertiesByTheNameTheGridShows(): void
    {
        self::assertSame(
            ['classes' => [self::GLOBAL_LEVEL, self::USER_LEVEL], 'fields' => ['titles']],
            $this->provider->getMatchingFields('titl')
        );
    }

    public function testMatchesAPropertyRenamedForTheAuditByItsNewName(): void
    {
        self::assertSame(['titles'], $this->provider->getMatchingFields('Title')['fields']);
        self::assertSame(['parentKey'], $this->provider->getMatchingFields('parent')['fields']);
    }

    public function testMatchesNothingWithoutATerm(): void
    {
        self::assertSame(['classes' => [], 'fields' => []], $this->provider->getMatchingFields('  '));
    }

    private function createFieldConfig(string $field, string $label): ConfigInterface&MockObject
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::any())
            ->method('getId')
            ->willReturn(new FieldConfigId('entity', MenuUpdate::class, $field));
        $config->expects(self::any())
            ->method('get')
            ->with('label')
            ->willReturn($label);

        return $config;
    }

    private function createEntityConfig(): ConfigInterface&MockObject
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::any())
            ->method('getId')
            ->willReturn(new EntityConfigId('entity', MenuUpdate::class));

        return $config;
    }
}
