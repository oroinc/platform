<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\Repository\NavigationRepositoryInterface;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\NavigationItemStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class NavigationItemsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ItemFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $itemFactory;

    /** @var UrlMatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlMatcher;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var NavigationItemsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->itemFactory = $this->createMock(ItemFactory::class);
        $this->urlMatcher = $this->createMock(UrlMatcherInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new NavigationItemsProvider($this->doctrineHelper, $this->itemFactory, $this->urlMatcher);
        $this->provider->setFeatureChecker($this->featureChecker);
    }

    public function testGetNavigationItems(): void
    {
        $type = 'sample-type';

        $user = $this->createMock(UserInterface::class);
        $organization = $this->createMock(Organization::class);
        $allItems = [
            ['url' => '/path-no-route'],
            ['url' => '/path-with-route'],
            ['route' => 'route_enabled'],
            ['route' => 'route_disabled'],
        ];

        $this->itemFactory->expects(self::once())
            ->method('createItem')
            ->with($type, [])
            ->willReturn(new NavigationItemStub());

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(NavigationItemStub::class)
            ->willReturn($repo = $this->createMock(NavigationRepositoryInterface::class));

        $repo->expects(self::once())
            ->method('getNavigationItems')
            ->with($user, $organization, $type, [])
            ->willReturn($allItems);

        $this->urlMatcher->expects(self::exactly(2))
            ->method('match')
            ->willReturnMap([
                ['/path-no-route', []],
                ['/path-with-route', ['_route' => 'route_enabled']]
            ]);

        $this->featureChecker->expects(self::exactly(3))
            ->method('isResourceEnabled')
            ->willReturnMap([
                ['route_enabled', 'routes', null, true],
                ['route_disabled', 'routes', null, false],
            ]);

        self::assertEquals(
            [
                ['url' => '/path-with-route'],
                ['route' => 'route_enabled'],
            ],
            $this->provider->getNavigationItems($user, $organization, $type)
        );
    }
}
