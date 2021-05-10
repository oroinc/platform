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
    public function testGetTitles(array $duplicatedCount, array $expectedTitles): void
    {
        $navigationItem = new NavigationItemStub([
            'title' => self::TITLE_TEMPLATE,
            'organization' => $organization = $this->createMock(OrganizationInterface::class),
            'user' => $user = $this->createMock(AbstractUser::class),
        ]);

        $this->titleService->expects(self::exactly(2))
            ->method('render')
            ->willReturnMap([
                [[], self::TITLE_TEMPLATE, null, null, true, false, 'sample-title'],
                [[], self::TITLE_TEMPLATE, null, null, true, true, 'sample-title-short']
            ]);

        $pinbarTabRepository = $this->createMock(PinbarTabRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(PinbarTab::class)
            ->willReturn($pinbarTabRepository);
        $with = [];
        $will = [];
        foreach ($duplicatedCount as [$title, $count]) {
            $with[] = [$title, $user, $organization];
            $will[] = $count;
        }
        $pinbarTabRepository->expects(self::exactly(count($duplicatedCount)))
            ->method('countPinbarTabDuplicatedTitles')
            ->withConsecutive(...$with)
            ->willReturnOnConsecutiveCalls(...$will);

        $titles = $this->provider->getTitles($navigationItem);

        self::assertEquals($expectedTitles, $titles);
    }

    public function getTitlesDataProvider(): array
    {
        return [
            'no duplicated titles' => [
                'duplicatedCount' => [['sample-title-short', 0]],
                'expectedTitles' => ['sample-title', 'sample-title-short'],
            ],
            '1 duplicated title' => [
                'duplicatedCount' => [['sample-title-short', 1], ['sample-title-short (2)', 0]],
                'expectedTitles' => ['sample-title (2)', 'sample-title-short (2)'],
            ],
            '2 duplicated title' => [
                'duplicatedCount' => [['sample-title-short', 2], ['sample-title-short (3)', 0]],
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

        $this->titleService->expects(self::exactly(2))
            ->method('render')
            ->willReturnMap([
                [[], self::TITLE_TEMPLATE, null, null, true, false, 'sample-title'],
                [[], self::TITLE_TEMPLATE, null, null, true, true, 'sample-title-short']
            ]);

        $pinbarTabRepository = $this->createMock(PinbarTabRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(PinbarTab::class)
            ->willReturn($pinbarTabRepository);
        $pinbarTabRepository->expects(self::exactly(3))
            ->method('countPinbarTabDuplicatedTitles')
            ->withConsecutive(
                ['sample-title-short', $user, $organization],
                ['sample-title-short (2)', $user, $organization],
                ['sample-title-short (3)', $user, $organization]
            )
            ->willReturnOnConsecutiveCalls(
                1,
                1,
                0
            );

        $titles = $this->provider->getTitles($navigationItem);

        self::assertEquals(['sample-title (3)', 'sample-title-short (3)'], $titles);
    }
}
