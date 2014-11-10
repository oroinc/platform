<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\UIBundle\ContentProvider\CurrentRouteContentProvider;
use Symfony\Component\HttpFoundation\Request;

class CurrentRouteContentProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var CurrentRouteContentProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->request = new Request();
        $this->provider = new CurrentRouteContentProvider();
    }

    public function testGetContent()
    {
        $this->request->attributes->set('_master_request_route', 'test_route');
        $this->provider->setRequest($this->request);
        $this->assertEquals('test_route', $this->provider->getContent());
    }

    public function testGetContentNoRequest()
    {
        $this->assertNull($this->provider->getContent());
    }

    public function testGetName()
    {
        $this->assertEquals('currentRoute', $this->provider->getName());
    }
}
