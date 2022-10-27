<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\TitleShortContentProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;

class TitleShortContentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TitleServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $titleService;

    /** @var TitleShortContentProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->titleService = $this->createMock(TitleServiceInterface::class);

        $this->provider = new TitleShortContentProvider($this->titleService);
    }

    public function testGetContent()
    {
        $this->titleService->expects($this->once())
            ->method('render')
            ->with([], null, null, null, true, true)
            ->willReturn('title_content');

        $this->assertEquals('title_content', $this->provider->getContent());
    }
}
