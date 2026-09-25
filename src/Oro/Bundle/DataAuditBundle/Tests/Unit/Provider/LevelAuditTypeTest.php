<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditFieldLabelProvider;
use Oro\Bundle\DataAuditBundle\Provider\ConfigAuditLevelProvider;
use Oro\Bundle\DataAuditBundle\Provider\LevelAuditType;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditFieldLabelProvider;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditLevelProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class LevelAuditTypeTest extends TestCase
{
    private const string GLOBAL_MENU = 'Oro\Bundle\NavigationBundle\GlobalBackOfficeMenu';
    private const string USER_MENU = 'Oro\Bundle\NavigationBundle\UserBackOfficeMenu';

    private ConfigAuditFieldLabelProvider&MockObject $configFieldLabelProvider;
    private MenuAuditLevelProvider&MockObject $menuLevelProvider;
    private MenuAuditFieldLabelProvider&MockObject $menuFieldLabelProvider;
    private LevelAuditType $configAuditType;
    private LevelAuditType $menuAuditType;

    #[\Override]
    protected function setUp(): void
    {
        $this->configFieldLabelProvider = $this->createMock(ConfigAuditFieldLabelProvider::class);
        $this->configAuditType = new LevelAuditType(
            new ConfigAuditLevelProvider(['global' => null, 'website' => 'Some\Website', 'portal' => null]),
            $this->configFieldLabelProvider,
            $this->createTranslator([
                'oro.dataaudit.config.type.system' => 'Configuration: System',
                'oro.dataaudit.config.type.website' => 'Configuration: Website',
            ])
        );

        $this->menuLevelProvider = $this->createMock(MenuAuditLevelProvider::class);
        $this->menuLevelProvider->expects(self::any())
            ->method('all')
            ->willReturn([self::GLOBAL_MENU => 'global', self::USER_MENU => 'user']);
        $this->menuLevelProvider->expects(self::any())
            ->method('getLabelKey')
            ->willReturnCallback(static fn (string $objectClass): ?string => match ($objectClass) {
                self::GLOBAL_MENU => 'oro.dataaudit.back_office_menu.type.global',
                self::USER_MENU => 'oro.dataaudit.back_office_menu.type.user',
                default => null,
            });
        $this->menuLevelProvider->expects(self::any())
            ->method('getGenericLabel')
            ->willReturn('Back-Office Menu: User');

        $this->menuFieldLabelProvider = $this->createMock(MenuAuditFieldLabelProvider::class);
        $this->menuAuditType = new LevelAuditType(
            $this->menuLevelProvider,
            $this->menuFieldLabelProvider,
            $this->createTranslator(['oro.dataaudit.back_office_menu.type.global' => 'Back-Office Menu: Global'])
        );
    }

    public function testEveryLevelBecomesAType(): void
    {
        self::assertSame(
            [
                'Oro\Bundle\ConfigBundle\SystemConfiguration' => 'Configuration: System',
                'Oro\Bundle\ConfigBundle\WebsiteConfiguration' => 'Configuration: Website',
                'Oro\Bundle\ConfigBundle\PortalConfiguration' => 'Configuration: Portal',
            ],
            $this->configAuditType->getTypes()
        );
        self::assertSame(
            [
                self::GLOBAL_MENU => 'Back-Office Menu: Global',
                self::USER_MENU => 'Back-Office Menu: User',
            ],
            $this->menuAuditType->getTypes()
        );
    }

    public function testNamesItsOwnTypesOnly(): void
    {
        self::assertSame(
            'Configuration: Website',
            $this->configAuditType->getTypeLabel('Oro\Bundle\ConfigBundle\WebsiteConfiguration')
        );
        self::assertSame(
            'Configuration: My Custom Portal',
            $this->configAuditType->getTypeLabel('Oro\Bundle\ConfigBundle\MyCustomPortalConfiguration')
        );
        self::assertNull($this->configAuditType->getTypeLabel('Oro\Bundle\UserBundle\Entity\User'));
        self::assertNull($this->configAuditType->getTypeLabel(self::GLOBAL_MENU));

        $this->menuLevelProvider->expects(self::any())
            ->method('isType')
            ->willReturnCallback(static fn (?string $objectClass): bool => \in_array(
                $objectClass,
                [self::GLOBAL_MENU, self::USER_MENU],
                true
            ));

        self::assertSame('Back-Office Menu: Global', $this->menuAuditType->getTypeLabel(self::GLOBAL_MENU));
        self::assertNull($this->menuAuditType->getTypeLabel('Oro\Bundle\ConfigBundle\SystemConfiguration'));
    }

    public function testNamesAChangedFieldTheWayItsDomainNamesIt(): void
    {
        $this->configFieldLabelProvider->expects(self::once())
            ->method('getLabel')
            ->with('Oro\Bundle\ConfigBundle\SystemConfiguration', 'oro_test.foo')
            ->willReturn('General Setup › Foo');
        $this->menuFieldLabelProvider->expects(self::once())
            ->method('getLabel')
            ->with(self::GLOBAL_MENU, 'titles')
            ->willReturn('Title');

        self::assertSame(
            'General Setup › Foo',
            $this->configAuditType->getFieldLabel('Oro\Bundle\ConfigBundle\SystemConfiguration', 'oro_test.foo')
        );
        self::assertSame('Title', $this->menuAuditType->getFieldLabel(self::GLOBAL_MENU, 'titles'));
    }

    public function testMatchesTheFieldsOfItsDomainAsASingleGroup(): void
    {
        $matching = ['classes' => [self::GLOBAL_MENU], 'fields' => ['titles']];
        $this->menuFieldLabelProvider->expects(self::once())
            ->method('getMatchingFields')
            ->with('Title')
            ->willReturn($matching);

        self::assertSame([$matching], $this->menuAuditType->getMatchingFieldGroups('Title'));
    }

    private function createTranslator(array $translations): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $key): string => $translations[$key] ?? $key);

        return $translator;
    }
}
