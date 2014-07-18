<?php
namespace Oro\Bundle\UIBundle\Tests\Unit\ContentProvider;

use Oro\Bundle\UIBundle\ContentProvider\ShowPinButtonContentProvider;
use Symfony\Component\HttpFoundation\Request;

class ShowPinButtonContentProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ShowPinButtonContentProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->request = new Request();
        $this->provider = new ShowPinButtonContentProvider($this->request);
    }

    /**
     * @dataProvider parametersDataProvider
     * @param string $route
     * @param bool $exception
     * @param bool $expected
     */
    public function testGetContent($route, $exception, $expected)
    {
        $this->request->attributes->set('_route', $route);
        $this->request->attributes->set('exception', $exception);
        $this->assertEquals($expected, $this->provider->getContent());
    }

    public function parametersDataProvider()
    {
        return array(
            array('oro_default', false, false),
            array('oro_default', true, false),
            array('oro_not_default', false, true),
            array('oro_not_default', true, false)
        );
    }

    public function testGetName()
    {
        $this->assertEquals('showPinButton', $this->provider->getName());
    }
}
