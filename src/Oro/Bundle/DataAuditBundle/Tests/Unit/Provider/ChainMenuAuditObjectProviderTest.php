<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\DataAuditBundle\Model\MenuAuditObject;
use Oro\Bundle\DataAuditBundle\Provider\ChainMenuAuditObjectProvider;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditObjectProviderInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use PHPUnit\Framework\TestCase;

class ChainMenuAuditObjectProviderTest extends TestCase
{
    public function testAsksEveryKindOfMenuUntilOneOfThemKnowsTheItem(): void
    {
        $auditObject = new MenuAuditObject('Oro\Bundle\CommerceMenuBundle\GlobalStorefrontMenu', 'menu', 5, 'item');
        $chainProvider = new ChainMenuAuditObjectProvider([
            $this->createProvider(null),
            $this->createProvider($auditObject),
            $this->createProvider(new MenuAuditObject('Acme\Bundle\DemoBundle\GlobalDemoMenu', 'menu', 5, 'item')),
        ]);

        self::assertSame($auditObject, $chainProvider->getAuditObject(new MenuUpdate()));
    }

    public function testKnowsNothingAboutAnItemNoKindOfMenuKnows(): void
    {
        $chainProvider = new ChainMenuAuditObjectProvider([$this->createProvider(null)]);

        self::assertNull($chainProvider->getAuditObject(new MenuUpdate()));
    }

    private function createProvider(?MenuAuditObject $auditObject): MenuAuditObjectProviderInterface
    {
        $provider = $this->createMock(MenuAuditObjectProviderInterface::class);
        $provider->expects(self::any())
            ->method('getAuditObject')
            ->willReturn($auditObject);

        return $provider;
    }
}
