<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\TitleSerializedContentProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;

class TitleSerializedContentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TitleServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $titleService;

    /** @var TitleSerializedContentProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->titleService = $this->createMock(TitleServiceInterface::class);

        $this->provider = new TitleSerializedContentProvider($this->titleService);
    }

    public function testGetContent()
    {
        $this->titleService->expects($this->once())
            ->method('getSerialized')
            ->willReturn('title_content');

        $this->assertEquals('title_content', $this->provider->getContent());
    }
}
