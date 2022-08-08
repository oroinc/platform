<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\UIBundle\ContentProvider\CurrentRouteContentProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CurrentRouteContentProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Request */
    private $request;

    /** @var RequestStack */
    private $requestStack;

    /** @var CurrentRouteContentProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->request = new Request();
        $this->requestStack = new RequestStack();
        $this->provider = new CurrentRouteContentProvider($this->requestStack);
    }

    public function testGetContent()
    {
        $this->request->attributes->set('_master_request_route', 'test_route');
        $this->requestStack->push($this->request);
        $this->assertEquals('test_route', $this->provider->getContent());
    }

    public function testGetContentNoRequest()
    {
        $this->assertNull($this->provider->getContent());
    }
}
