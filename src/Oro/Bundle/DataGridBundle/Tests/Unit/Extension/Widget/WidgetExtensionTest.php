<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Widget;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Widget\WidgetExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag as HttpParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WidgetExtensionTest extends TestCase
{
    private RequestStack|MockObject $requestStack;

    private WidgetExtension $extension;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->extension = new WidgetExtension($this->requestStack);
        $this->extension->setParameters(new ParameterBag());
    }

    public function testIsNotApplicable(): void
    {
        $request = $this->createRequest([]);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $configObject = DatagridConfiguration::create([]);
        $this->assertFalse($this->extension->isApplicable($configObject));
    }

    public function testIsApplicable(): void
    {
        $request = $this->createRequest(['_widgetId' => 1]);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $configObject = DatagridConfiguration::create([]);
        $this->assertTrue($this->extension->isApplicable($configObject));
    }

    public function testVisitMetadata()
    {
        $request = $this->createRequest(['_widgetId' => 1]);
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $config = DatagridConfiguration::create(['state' => []]);
        $metadata = MetadataObject::create([]);

        $this->extension->visitMetadata($config, $metadata);
        $this->assertEquals(1, $metadata->offsetGetByPath('[state][widgetId]'));
    }

    private function createRequest(array $params): Request
    {
        $currentRequest = Request::create('/', Request::METHOD_GET);
        $currentRequest->query = new HttpParameterBag($params);

        return $currentRequest;
    }
}
