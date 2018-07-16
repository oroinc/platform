<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Request;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;
use Oro\Bundle\LayoutBundle\Request\LayoutHelper;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LayoutHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LayoutHelper
     */
    protected $helper;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestStack;

    public function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->helper = new LayoutHelper($this->requestStack);
    }

    /**
     * @dataProvider layoutHelperDataProvider
     * @param Request|\PHPUnit\Framework\MockObject\MockObject|null $request
     * @param LayoutAnnotation|\PHPUnit\Framework\MockObject\MockObject|null $annotation
     */
    public function testGetLayoutAnnotation(
        \PHPUnit\Framework\MockObject\MockObject $request = null,
        \PHPUnit\Framework\MockObject\MockObject $annotation = null
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
                'request' => $this->createMock('Symfony\Component\HttpFoundation\Request'),
                'annotation' => null,
            ],
            [
                'request' => $this->createMock('Symfony\Component\HttpFoundation\Request'),
                'annotation' => $this->getLayoutAnnotationMock(),
            ],
        ];
    }

    /**
     * @dataProvider layoutHelperDataProvider
     * @param Request|\PHPUnit\Framework\MockObject\MockObject|null $request
     * @param LayoutAnnotation|\PHPUnit\Framework\MockObject\MockObject|null $annotation
     */
    public function testIsLayoutRequest(
        \PHPUnit\Framework\MockObject\MockObject $request = null,
        \PHPUnit\Framework\MockObject\MockObject $annotation = null
    ) {
        $this->setUpRequestStack($request, $annotation);
        $this->assertEquals((bool)$annotation, $this->helper->isLayoutRequest($request));
    }

    /**
     * @dataProvider layoutHelperDataProvider
     * @param Request|\PHPUnit\Framework\MockObject\MockObject|null $request
     * @param LayoutAnnotation|\PHPUnit\Framework\MockObject\MockObject|null $annotation
     */
    public function testIsTemplateRequest(
        \PHPUnit\Framework\MockObject\MockObject $request = null,
        \PHPUnit\Framework\MockObject\MockObject $annotation = null
    ) {
        $this->setUpRequestStack($request, $annotation);
        $this->assertEquals(!(bool)$annotation, $this->helper->isTemplateRequest($request));
    }

    /**
     * @return LayoutAnnotation|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLayoutAnnotationMock()
    {
        return $this->getMockBuilder('Oro\Bundle\LayoutBundle\Annotation\Layout')->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param Request|\PHPUnit\Framework\MockObject\MockObject|null $request
     * @param LayoutAnnotation|\PHPUnit\Framework\MockObject\MockObject|null $annotation
     * @param bool $isTemplate
     */
    protected function setUpRequestStack(
        \PHPUnit\Framework\MockObject\MockObject $request = null,
        \PHPUnit\Framework\MockObject\MockObject $annotation = null,
        $isTemplate = false
    ) {
        if ($request) {
            $this->requestStack->expects($this->never())
                ->method('getCurrentRequest');
        } else {
            $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
            $this->requestStack->expects($this->once())
                ->method('getCurrentRequest')
                ->willReturn($request);
        }

        /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject $attributes */
        $attributes = $this->createMock('Symfony\Component\HttpFoundation\ParameterBag');
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
