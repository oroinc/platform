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

    public function layoutHelperDataProvider(): array
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
     */
    public function testIsLayoutRequest(?Request $request, ?LayoutAnnotation $annotation)
    {
        $this->setUpRequestStack($request, $annotation);
        $this->assertEquals(null !== $annotation, $this->helper->isLayoutRequest($request));
    }

    /**
     * @dataProvider layoutHelperDataProvider
     */
    public function testIsTemplateRequest(?Request $request, ?LayoutAnnotation $annotation)
    {
        $this->setUpRequestStack($request, $annotation);
        $this->assertEquals(null === $annotation, $this->helper->isTemplateRequest($request));
    }

    private function setUpRequestStack(?Request $request, ?LayoutAnnotation $annotation): void
    {
        if ($request) {
            $this->requestStack->expects($this->never())
                ->method('getCurrentRequest');
        } else {
            $request = $this->createMock(Request::class);
            $this->requestStack->expects($this->once())
                ->method('getCurrentRequest')
                ->willReturn($request);
        }

        $attributes = $this->createMock(ParameterBag::class);
        $request->attributes = $attributes;
        $attributes->expects($this->once())
            ->method('get')
            ->with('_layout')
            ->willReturn($annotation);
    }
}
