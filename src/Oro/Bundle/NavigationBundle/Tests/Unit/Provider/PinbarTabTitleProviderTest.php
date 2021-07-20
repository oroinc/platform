<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository;
use Oro\Bundle\NavigationBundle\Provider\PinbarTabTitleProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleService;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\NavigationItemStub;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class PinbarTabTitleProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TITLE_TEMPLATE = 'title-template';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TitleService|\PHPUnit\Framework\MockObject\MockObject */
    private $titleService;

    /** @var PinbarTabTitleProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->titleService = $this->createMock(TitleService::class);

        $this->provider = new PinbarTabTitleProvider($this->doctrineHelper, $this->titleService);
    }

    public function testGetTitlesWhenNoTitle(): void
    {
        $titles = $this->provider->getTitles(new NavigationItemStub());

        self::assertEquals(['', ''], $titles);
    }

    /**
     * @dataProvider getTitlesDataProvider
     */
    public function testGetTitles(int $duplicatedCount, array $expectedTitles): void
    {
        $navigationItem = new NavigationItemStub([
            'title' => self::TITLE_TEMPLATE,
            'organization' => $organization = $this->createMock(OrganizationInterface::class),
            'user' => $user = $this->createMock(AbstractUser::class),
        ]);

        $this->titleService
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnMap([
                [[], self::TITLE_TEMPLATE, null, null, true, false, $title = 'sample-title'],
                [[], self::TITLE_TEMPLATE, null, null, true, true, $titleShort = 'sample-title-short'],
            ]);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(PinbarTab::class)
            ->willReturn($pinbarTabRepository = $this->createMock(PinbarTabRepository::class));

        $pinbarTabRepository
            ->expects(self::at(0))
            ->method('countPinbarTabDuplicatedTitles')
            ->with($titleShort, $user, $organization)
            ->willReturn($duplicatedCount);

        $titles = $this->provider->getTitles($navigationItem);

        self::assertEquals($expectedTitles, $titles);
    }

    public function getTitlesDataProvider(): array
    {
        return [
            'no duplicated titles' => [
                'duplicatedCount' => 0,
                'expectedTitles' => ['sample-title', 'sample-title-short'],
            ],
            '1 duplicated title' => [
                'duplicatedCount' => 1,
                'expectedTitles' => ['sample-title (2)', 'sample-title-short (2)'],
            ],
            '2 duplicated title' => [
                'duplicatedCount' => 2,
                'expectedTitles' => ['sample-title (3)', 'sample-title-short (3)'],
            ],
        ];
    }

    public function testGetTitlesWithSomeTitlesAlreadyOccupied()
    {
        $navigationItem = new NavigationItemStub([
            'title' => self::TITLE_TEMPLATE,
            'organization' => $organization = $this->createMock(OrganizationInterface::class),
            'user' => $user = $this->createMock(AbstractUser::class),
        ]);

        $this->titleService
            ->expects(self::exactly(2))
            ->method('render')
            ->willReturnMap([
                [[], self::TITLE_TEMPLATE, null, null, true, false, $title = 'sample-title'],
                [[], self::TITLE_TEMPLATE, null, null, true, true, $titleShort = 'sample-title-short'],
            ]);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(PinbarTab::class)
            ->willReturn($pinbarTabRepository = $this->createMock(PinbarTabRepository::class));

        $pinbarTabRepository
            ->expects(self::at(0))
            ->method('countPinbarTabDuplicatedTitles')
            ->with($titleShort, $user, $organization)
            ->willReturn(1);

        $pinbarTabRepository
            ->expects(self::at(1))
            ->method('countPinbarTabDuplicatedTitles')
            ->with('sample-title-short (2)', $user, $organization)
            ->willReturn(1);

        $pinbarTabRepository
            ->expects(self::at(2))
            ->method('countPinbarTabDuplicatedTitles')
            ->with('sample-title-short (3)', $user, $organization)
            ->willReturn(0);

        $titles = $this->provider->getTitles($navigationItem);

        self::assertEquals(['sample-title (3)', 'sample-title-short (3)'], $titles);
    }
}
