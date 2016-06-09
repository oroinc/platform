<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\LayoutBundle\Layout\Extension\RootIdContextConfigurator;

class RootIdContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var RootIdContextConfigurator */
    protected $contextConfigurator;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    protected function setUp()
    {
        $this->requestStack = new RequestStack();
        $this->contextConfigurator = new RootIdContextConfigurator($this->requestStack);
    }

    protected function assertRootId($expectedRootId, $setRootId = null)
    {
        $context = new LayoutContext();
        if ($setRootId) {
            $context['root_id'] = $setRootId;
        }
        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals($expectedRootId, $context['root_id']);
    }

    protected function createRequest()
    {
        $request = new Request();
        $this->requestStack->push($request);
        return $request;
    }

    public function testConfigureContextByQuery()
    {
        $this->createRequest()->query->set('layout_root_id', 'query_root_id');
        $this->assertRootId('query_root_id');
    }

    public function testConfigureContextOverride()
    {
        $this->createRequest()->query->set('layout_root_id', 'query_root_id');
        $this->assertRootId('defined_root_id', 'defined_root_id');
    }

    public function testConfigureContextNoWidget()
    {
        $this->createRequest();
        $this->assertRootId(null);
    }

    public function testConfigureContextWithoutRequest()
    {
        $this->assertRootId(null);
    }
}
