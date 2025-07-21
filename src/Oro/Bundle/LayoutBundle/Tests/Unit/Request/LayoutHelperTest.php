<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Request;

use Oro\Bundle\LayoutBundle\Attribute\Layout as LayoutAttribute;
use Oro\Bundle\LayoutBundle\Request\LayoutHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LayoutHelperTest extends TestCase
{
    private RequestStack&MockObject $requestStack;
    private LayoutHelper $helper;

    #[\Override]
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
                'annotation' => $this->createMock(LayoutAttribute::class),
            ],
            [
                'request' => $this->createMock(Request::class),
                'annotation' => null,
            ],
            [
                'request' => $this->createMock(Request::class),
                'annotation' => $this->createMock(LayoutAttribute::class),
            ],
        ];
    }

    /**
     * @dataProvider layoutHelperDataProvider
     */
    public function testIsLayoutRequest(?Request $request, ?LayoutAttribute $attribute): void
    {
        $this->setUpRequestStack($request, $attribute);
        $this->assertEquals(null !== $attribute, $this->helper->isLayoutRequest($request));
    }

    /**
     * @dataProvider layoutHelperDataProvider
     */
    public function testIsTemplateRequest(?Request $request, ?LayoutAttribute $attribute): void
    {
        $this->setUpRequestStack($request, $attribute);
        $this->assertEquals(null === $attribute, $this->helper->isTemplateRequest($request));
    }

    private function setUpRequestStack(?Request $request, ?LayoutAttribute $attribute): void
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
            ->willReturn($attribute);
    }
}
