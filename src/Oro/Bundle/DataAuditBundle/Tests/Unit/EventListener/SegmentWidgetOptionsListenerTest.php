<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\DataAuditBundle\EventListener\SegmentWidgetOptionsListener;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;

class SegmentWidgetOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $httpKernel;
    protected $request;

    public function setUp()
    {
        $this->httpKernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor('Symfony\Component\HttpFoundation\Request')
            ->getMock();
    }

    public function testListener()
    {
        $options = [
            'filters'      => [],
            'column'       => [],
            'extensions'   => [],
            'fieldsLoader' => [
                'entityChoice'      => 'choice',
                'loadingMaskParent' => 'loadingMask',
                'confirmMessage'    => 'confirmMessage',
            ],
        ];

        $auditFields = json_encode(['field1', 'field2']);

        $expectedOptions = [
            'filters'    => [],
            'column'     => [],
            'extensions' => [
                'orodataaudit/js/app/components/segment-component-extension',
            ],
            'fieldsLoader' => [
                'entityChoice'      => 'choice',
                'loadingMaskParent' => 'loadingMask',
                'confirmMessage'    => 'confirmMessage',
            ],
            'auditFieldsLoader' => [
                'entityChoice'      => 'choice',
                'loadingMaskParent' => 'loadingMask',
                'confirmMessage'    => 'confirmMessage',
                'router'            => 'oro_api_get_audit_fields',
                'routingParams'    => [],
                'fieldsData'        => $auditFields,
            ],
        ];

        $subRequest = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request->expects($this->once())
            ->method('duplicate')
            ->with(['_format' => 'json'], null, ['_controller' => 'OroDataAuditBundle:Api/Rest/Audit:getFields'])
            ->will($this->returnValue($subRequest));

        $this->httpKernel->expects($this->once())
            ->method('handle')
            ->with($subRequest)
            ->will($this->returnValue(new Response($auditFields)));

        $listener = new SegmentWidgetOptionsListener($this->httpKernel);
        $listener->setRequest($this->request);
        $event = new WidgetOptionsLoadEvent($options);
        $listener->onLoad($event);

        $this->assertEquals($expectedOptions, $event->getWidgetOptions());
    }
}
