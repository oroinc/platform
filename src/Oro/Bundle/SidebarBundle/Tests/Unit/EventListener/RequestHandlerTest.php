<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SidebarBundle\EventListener\RequestHandler;
use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WidgetDefinitionRegistry
     */
    protected $widgetDefinitionsRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $assetHelper;


    protected function setUp()
    {
        $this->widgetDefinitionsRegistry = $this
            ->getMockBuilder('Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->assetHelper = $this->getMockBuilder('Symfony\Component\Asset\Packages')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new RequestHandler($this->widgetDefinitionsRegistry, $this->assetHelper);
    }

    /**
     * @param array $definitions
     * @param bool $expects
     *
     * @dataProvider definitionDataProvider
     */
    public function testOnKernelRequest(array $definitions, $expects)
    {
        /** @var GetResponseEvent $event */
        $event = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->widgetDefinitionsRegistry->expects($this->once())
            ->method('getWidgetDefinitions')
            ->will($this->returnValue(new ArrayCollection($definitions)));

        if ($expects) {
            $this->assetHelper->expects($this->exactly($expects))
                ->method('getUrl')
                ->with($this->isType('string'));
        }

        $this->handler->onKernelRequest($event);
    }

    /**
     * @return array
     */
    public function definitionDataProvider()
    {
        return [
            'empty' => [[], 0],
            'without icon' => [[['name' => 'widget']], 0],
            'with icon' => [[['icon' => 'icon.png']], 1],
            'two with icon' => [[['icon' => 'icon.png'], ['name' => 'widget']], 1],
            'two with icons' => [[['icon' => 'icon.png'], ['icon' => 'widget.png']], 2],
        ];
    }
}
