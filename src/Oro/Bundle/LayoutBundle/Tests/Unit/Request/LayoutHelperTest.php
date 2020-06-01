<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Request;

use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;
use Oro\Bundle\LayoutBundle\Request\LayoutHelper;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LayoutHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var LayoutHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->helper = new LayoutHelper($this->requestStack);
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
                'annotation' => $this->createMock(LayoutAnnotation::class),
            ],
            [
                'request' => $this->createMock(Request::class),
                'annotation' => null,
            ],
            [
                'request' => $this->createMock(Request::class),
                'annotation' => $this->createMock(LayoutAnnotation::class),
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
        $this->assertEquals(null !== $annotation, $this->helper->isLayoutRequest($request));
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
        $this->assertEquals(null === $annotation, $this->helper->isTemplateRequest($request));
    }

    /**
     * @param Request|\PHPUnit\Framework\MockObject\MockObject|null $request
     * @param LayoutAnnotation|\PHPUnit\Framework\MockObject\MockObject|null $annotation
     */
    private function setUpRequestStack(
        \PHPUnit\Framework\MockObject\MockObject $request = null,
        \PHPUnit\Framework\MockObject\MockObject $annotation = null
    ) {
        if ($request) {
            $this->requestStack->expects($this->never())
                ->method('getCurrentRequest');
        } else {
            $request = $this->createMock(Request::class);
            $this->requestStack->expects($this->once())
                ->method('getCurrentRequest')
                ->willReturn($request);
        }

        /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject $attributes */
        $attributes = $this->createMock(ParameterBag::class);
        $request->attributes = $attributes;
        $attributes->expects($this->at(0))
            ->method('get')
            ->with('_layout')
            ->willReturn($annotation);
    }
}
