<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\TitleContentProvider;

class TitleContentProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $titleService;

    /**
     * @var TitleContentProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->titleService = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface')
            ->getMock();

        $this->provider = new TitleContentProvider($this->titleService);
    }

    public function testGetContent()
    {
        $this->titleService->expects($this->once())
            ->method('render')
            ->with(array(), null, null, null, true)
            ->will($this->returnValue('title_content'));
        $this->assertEquals('title_content', $this->provider->getContent());
    }

    public function testGetName()
    {
        $this->assertEquals('title', $this->provider->getName());
    }
}
