<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\TitleSerializedContentProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TitleSerializedContentProviderTest extends TestCase
{
    private TitleServiceInterface&MockObject $titleService;
    private TitleSerializedContentProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->titleService = $this->createMock(TitleServiceInterface::class);

        $this->provider = new TitleSerializedContentProvider($this->titleService);
    }

    public function testGetContent(): void
    {
        $this->titleService->expects($this->once())
            ->method('getSerialized')
            ->willReturn('title_content');

        $this->assertEquals('title_content', $this->provider->getContent());
    }
}
