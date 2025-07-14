<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\TitleContentProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TitleContentProviderTest extends TestCase
{
    private TitleServiceInterface&MockObject $titleService;
    private TitleContentProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->titleService = $this->createMock(TitleServiceInterface::class);

        $this->provider = new TitleContentProvider($this->titleService);
    }

    public function testGetContent(): void
    {
        $this->titleService->expects($this->once())
            ->method('render')
            ->with([], null, null, null, true)
            ->willReturn('title_content');

        $this->assertEquals('title_content', $this->provider->getContent());
    }
}
