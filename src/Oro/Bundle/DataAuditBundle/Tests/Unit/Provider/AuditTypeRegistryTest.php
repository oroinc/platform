<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\DataAuditBundle\Provider\AuditTypeInterface;
use Oro\Bundle\DataAuditBundle\Provider\AuditTypeRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuditTypeRegistryTest extends TestCase
{
    private AuditTypeInterface&MockObject $configType;
    private AuditTypeInterface&MockObject $menuType;
    private AuditTypeRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->configType = $this->createMock(AuditTypeInterface::class);
        $this->menuType = $this->createMock(AuditTypeInterface::class);

        $this->registry = new AuditTypeRegistry([$this->configType, $this->menuType]);
    }

    public function testGetTypesMergesEveryType(): void
    {
        $this->configType->expects(self::once())
            ->method('getTypes')
            ->willReturn(['Config\SystemConfiguration' => 'Configuration: System']);
        $this->menuType->expects(self::once())
            ->method('getTypes')
            ->willReturn(['Menu\WebsiteStorefrontMenu' => 'Storefront Menu: Website']);

        self::assertSame(
            [
                'Config\SystemConfiguration' => 'Configuration: System',
                'Menu\WebsiteStorefrontMenu' => 'Storefront Menu: Website',
            ],
            $this->registry->getTypes()
        );
    }

    public function testGetTypeLabelReturnsTheFirstTypeThatKnowsTheClass(): void
    {
        $this->configType->expects(self::once())
            ->method('getTypeLabel')
            ->with('Menu\WebsiteStorefrontMenu')
            ->willReturn(null);
        $this->menuType->expects(self::once())
            ->method('getTypeLabel')
            ->with('Menu\WebsiteStorefrontMenu')
            ->willReturn('Storefront Menu: Website');

        self::assertSame('Storefront Menu: Website', $this->registry->getTypeLabel('Menu\WebsiteStorefrontMenu'));
    }

    public function testGetTypeLabelReturnsNullForAnEntityOfTheApplication(): void
    {
        $this->configType->expects(self::once())
            ->method('getTypeLabel')
            ->willReturn(null);
        $this->menuType->expects(self::once())
            ->method('getTypeLabel')
            ->willReturn(null);

        self::assertNull($this->registry->getTypeLabel('Oro\Bundle\UserBundle\Entity\User'));
    }

    public function testGetFieldLabelReturnsTheFirstNameFound(): void
    {
        $this->configType->expects(self::once())
            ->method('getFieldLabel')
            ->with('Config\SystemConfiguration', 'oro_test.foo')
            ->willReturn('General › Foo');
        $this->menuType->expects(self::never())
            ->method('getFieldLabel');

        self::assertSame(
            'General › Foo',
            $this->registry->getFieldLabel('Config\SystemConfiguration', 'oro_test.foo')
        );
    }

    public function testGetMatchingFieldGroupsKeepsEveryDomainScopedOnItsOwn(): void
    {
        $this->configType->expects(self::once())
            ->method('getMatchingFieldGroups')
            ->with('promo')
            ->willReturn([['classes' => [], 'fields' => ['oro_promotion.enabled']]]);
        $this->menuType->expects(self::once())
            ->method('getMatchingFieldGroups')
            ->with('promo')
            ->willReturn([['classes' => ['Menu\WebsiteStorefrontMenu'], 'fields' => ['Promo Banner']]]);

        self::assertSame(
            [
                ['classes' => [], 'fields' => ['oro_promotion.enabled']],
                ['classes' => ['Menu\WebsiteStorefrontMenu'], 'fields' => ['Promo Banner']],
            ],
            $this->registry->getMatchingFieldGroups('promo')
        );
    }

    public function testWithoutAnyAuditType(): void
    {
        $registry = new AuditTypeRegistry([]);

        self::assertSame([], $registry->getTypes());
        self::assertNull($registry->getTypeLabel('Some\Class'));
        self::assertNull($registry->getFieldLabel('Some\Class', 'field'));
        self::assertSame([], $registry->getMatchingFieldGroups('term'));
    }
}
