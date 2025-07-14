<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\TitleShortContentProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TitleShortContentProviderTest extends TestCase
{
    private TitleServiceInterface&MockObject $titleService;
    private TitleShortContentProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->titleService = $this->createMock(TitleServiceInterface::class);

        $this->provider = new TitleShortContentProvider($this->titleService);
    }

    public function testGetContent(): void
    {
        $this->titleService->expects($this->once())
            ->method('render')
            ->with([], null, null, null, true, true)
            ->willReturn('title_content');

        $this->assertEquals('title_content', $this->provider->getContent());
    }
}
