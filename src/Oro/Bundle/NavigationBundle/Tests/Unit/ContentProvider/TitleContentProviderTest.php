<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\TitleContentProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;

class TitleContentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TitleServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $titleService;

    /** @var TitleContentProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->titleService = $this->createMock(TitleServiceInterface::class);

        $this->provider = new TitleContentProvider($this->titleService);
    }

    public function testGetContent()
    {
        $this->titleService->expects($this->once())
            ->method('render')
            ->with([], null, null, null, true)
            ->willReturn('title_content');

        $this->assertEquals('title_content', $this->provider->getContent());
    }
}
