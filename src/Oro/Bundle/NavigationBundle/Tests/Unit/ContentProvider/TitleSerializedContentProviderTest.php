<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\NavigationBundle\ContentProvider\TitleSerializedContentProvider;

class TitleSerializedContentProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $titleService;

    /**
     * @var TitleSerializedContentProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->titleService = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface')
            ->getMock();

        $this->provider = new TitleSerializedContentProvider($this->titleService);
    }

    public function testGetContent()
    {
        $this->titleService->expects($this->once())
            ->method('getSerialized')
            ->will($this->returnValue('title_content'));
        $this->assertEquals('title_content', $this->provider->getContent());
    }

    public function testGetName()
    {
        $this->assertEquals('titleSerialized', $this->provider->getName());
    }
}
