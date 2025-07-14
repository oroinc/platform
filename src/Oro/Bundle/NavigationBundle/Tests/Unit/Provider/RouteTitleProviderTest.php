<?php

declare(strict_types=1);

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\NavigationBundle\Provider\RouteTitleProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleService;
use Oro\Bundle\NavigationBundle\Provider\TitleTranslator;
use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RouteTitleProviderTest extends TestCase
{
    private TitleReaderRegistry&MockObject $readerRegistry;
    private TitleTranslator&MockObject $titleTranslator;
    private TitleService&MockObject $titleService;
    private RouteTitleProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->readerRegistry = $this->createMock(TitleReaderRegistry::class);
        $this->titleTranslator = $this->createMock(TitleTranslator::class);
        $this->titleService = $this->createMock(TitleService::class);

        $this->provider = new RouteTitleProvider($this->readerRegistry, $this->titleTranslator, $this->titleService);
    }

    public function testWhenNoTitle(): void
    {
        $routeName = 'sample_route';
        $this->readerRegistry->expects(self::once())
            ->method('getTitleByRoute')
            ->with($routeName)
            ->willReturn(null);

        $this->titleService->expects(self::never())
            ->method('createTitle');

        $this->titleTranslator->expects(self::never())
            ->method('trans');

        self::assertSame('', $this->provider->getTitle($routeName, 'frontend_menu'));
    }

    public function testWhenHasTitle(): void
    {
        $routeName = 'sample_route';
        $titleKey = 'sample_title';
        $menuName = 'frontend_menu';

        $this->readerRegistry->expects(self::once())
            ->method('getTitleByRoute')
            ->with($routeName)
            ->willReturn($titleKey);

        $title = 'oro.sample_title';
        $this->titleService->expects(self::once())
            ->method('createTitle')
            ->with($routeName, $titleKey, $menuName)
            ->willReturn($title);

        $translatedTitle = 'translated title';
        $this->titleTranslator->expects(self::once())
            ->method('trans')
            ->with($title)
            ->willReturn($translatedTitle);

        self::assertSame($translatedTitle, $this->provider->getTitle($routeName, $menuName));
    }
}
