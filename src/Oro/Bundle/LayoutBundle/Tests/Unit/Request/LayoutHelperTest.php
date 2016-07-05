<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Request;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;
use Oro\Bundle\LayoutBundle\Request\LayoutHelper;

class LayoutHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LayoutHelper
     */
    protected $helper;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    public function setUp()
    {
        $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $this->configManager = $this->getMock('Oro\Bundle\ConfigBundle\Config\ConfigManager', [], [], '', false);

        $this->helper = new LayoutHelper($this->requestStack, $this->configManager);
    }

    /**
     * @dataProvider layoutHelperDataProvider
     * @param Request|\PHPUnit_Framework_MockObject_MockObject|null $request
     * @param LayoutAnnotation|\PHPUnit_Framework_MockObject_MockObject|null $annotation
     */
    public function testGetLayoutAnnotation(
        \PHPUnit_Framework_MockObject_MockObject $request = null,
        \PHPUnit_Framework_MockObject_MockObject $annotation = null
    ) {
        $this->setUpRequestStack($request, $annotation);
        $this->assertEquals($annotation, $this->helper->getLayoutAnnotation($request));
    }

    /**
     * @return array
     */
    public function layoutHelperDataProvider()
    {
        return [
            [
                'request' => null,
                'annotation' => null,
            ],
            [
                'request' => null,
                'annotation' => $this->getLayoutAnnotationMock(),
            ],
            [
                'request' => $this->getMock('Symfony\Component\HttpFoundation\Request'),
                'annotation' => null,
            ],
            [
                'request' => $this->getMock('Symfony\Component\HttpFoundation\Request'),
                'annotation' => $this->getLayoutAnnotationMock(),
            ],
        ];
    }

    /**
     * @dataProvider layoutHelperDataProvider
     * @param Request|\PHPUnit_Framework_MockObject_MockObject|null $request
     * @param LayoutAnnotation|\PHPUnit_Framework_MockObject_MockObject|null $annotation
     */
    public function testIsLayoutRequest(
        \PHPUnit_Framework_MockObject_MockObject $request = null,
        \PHPUnit_Framework_MockObject_MockObject $annotation = null
    ) {
        $this->setUpRequestStack($request, $annotation);
        $this->assertEquals((bool)$annotation, $this->helper->isLayoutRequest($request));
    }

    /**
     * @dataProvider layoutHelperDataProvider
     * @param Request|\PHPUnit_Framework_MockObject_MockObject|null $request
     * @param LayoutAnnotation|\PHPUnit_Framework_MockObject_MockObject|null $annotation
     */
    public function testIsTemplateRequest(
        \PHPUnit_Framework_MockObject_MockObject $request = null,
        \PHPUnit_Framework_MockObject_MockObject $annotation = null
    ) {
        $this->setUpRequestStack($request, $annotation);
        $this->assertEquals(!(bool)$annotation, $this->helper->isTemplateRequest($request));
    }

    public function testIsProfilerEnabledTrue()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_layout.debug_block_info')
            ->will($this->returnValue(true));

        $this->assertTrue($this->helper->isProfilerEnabled());
    }

    public function testIsProfilerEnabledFalse()
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_layout.debug_block_info')
            ->will($this->returnValue(false));

        $this->assertFalse($this->helper->isProfilerEnabled());
    }

    /**
     * @return LayoutAnnotation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLayoutAnnotationMock()
    {
        return $this->getMockBuilder('Oro\Bundle\LayoutBundle\Annotation\Layout')->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param Request|\PHPUnit_Framework_MockObject_MockObject|null $request
     * @param LayoutAnnotation|\PHPUnit_Framework_MockObject_MockObject|null $annotation
     * @param bool $isTemplate
     */
    protected function setUpRequestStack(
        \PHPUnit_Framework_MockObject_MockObject $request = null,
        \PHPUnit_Framework_MockObject_MockObject $annotation = null,
        $isTemplate = false
    ) {
        if ($request) {
            $this->requestStack->expects($this->never())
                ->method('getCurrentRequest');
        } else {
            $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
            $this->requestStack->expects($this->once())
                ->method('getCurrentRequest')
                ->willReturn($request);
        }

        /** @var ParameterBag|\PHPUnit_Framework_MockObject_MockObject $attributes */
        $attributes = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
        $request->attributes = $attributes;
        $attributes->expects($this->at(0))
            ->method('get')
            ->with('_layout')
            ->willReturn($annotation);

        if ($isTemplate) {
            $template = $this->getMockBuilder('Sensio\Bundle\FrameworkExtraBundle\Configuration\Template')
                ->disableOriginalConstructor()->getMock();
            $attributes->expects($this->at(1))
                ->method('get')
                ->with('_template')
                ->willReturn($template);
        }
    }
}
