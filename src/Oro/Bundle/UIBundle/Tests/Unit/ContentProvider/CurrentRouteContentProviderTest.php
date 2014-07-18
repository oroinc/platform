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
        $this->provider = new CurrentRouteContentProvider($this->request);
    }

    public function testGetContent()
    {
        $this->request->attributes->set('_route', 'test_route');
        $this->assertEquals('test_route', $this->provider->getContent());
    }

    public function testGetName()
    {
        $this->assertEquals('currentRoute', $this->provider->getName());
    }
}
