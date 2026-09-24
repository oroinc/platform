<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Placeholder;

use Oro\Bundle\DataAuditBundle\Model\MenuAuditObject;
use Oro\Bundle\DataAuditBundle\Placeholder\MenuAuditFilter;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditObjectProviderInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuAuditFilterTest extends TestCase
{
    private MenuAuditObjectProviderInterface&MockObject $auditObjectProvider;
    private MenuAuditFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->auditObjectProvider = $this->createMock(MenuAuditObjectProviderInterface::class);
        $this->filter = new MenuAuditFilter($this->auditObjectProvider);
    }

    public function testTellsThatAMenuItemHasAHistoryOfItsOwn(): void
    {
        $this->auditObjectProvider->expects(self::once())
            ->method('getAuditObject')
            ->willReturn(new MenuAuditObject('Oro\Bundle\NavigationBundle\GlobalBackOfficeMenu', 'menu', 5, 'item'));

        self::assertTrue($this->filter->isMenuItemAuditable(new MenuUpdate()));
    }

    public function testTellsThatAMenuItemOfAnUnknownMenuHasNoHistory(): void
    {
        $this->auditObjectProvider->expects(self::once())
            ->method('getAuditObject')
            ->willReturn(null);

        self::assertFalse($this->filter->isMenuItemAuditable(new MenuUpdate()));
    }

    /**
     * @dataProvider notAMenuItemDataProvider
     */
    public function testAsksAboutNothingButAMenuItem(mixed $entity): void
    {
        $this->auditObjectProvider->expects(self::never())
            ->method('getAuditObject');

        self::assertFalse($this->filter->isMenuItemAuditable($entity));
    }

    public function notAMenuItemDataProvider(): array
    {
        return [
            'nothing at all' => [null],
            'another entity' => [new \stdClass()],
            'a plain value' => ['contact_us'],
        ];
    }
}
