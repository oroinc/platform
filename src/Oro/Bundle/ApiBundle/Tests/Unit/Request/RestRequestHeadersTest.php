<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;
use Oro\Component\ChainProcessor\ParameterValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;

class RestRequestHeadersTest extends \PHPUnit\Framework\TestCase
{
    /** @var Request */
    private $request;

    /** @var RestRequestHeaders */
    private $requestHeaders;

    protected function setUp()
    {
        $this->request = Request::create('http://test.com');
        $this->request->headers->set('prm1', 'val1');
        $this->request->headers->set('PRM-2', 'val2');

        $this->requestHeaders = new RestRequestHeaders($this->request);
    }

    public function testHasWhenInternalStorageIsNotInitialized()
    {
        self::assertTrue($this->requestHeaders->has('prm1'));
        self::assertTrue($this->requestHeaders->has('prm_2'));
    }

    public function testGetWhenInternalStorageIsNotInitialized()
    {
        self::assertEquals('val1', $this->requestHeaders->get('prm1'));
        self::assertEquals('val2', $this->requestHeaders->get('prm_2'));
    }

    public function testGetLazyValueWhenInternalStorageIsNotInitialized()
    {
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with('val1')
            ->willReturn(true);
        $resolver->expects(self::once())
            ->method('resolve')
            ->with('val1')
            ->willReturn('resolved_val1');

        $this->requestHeaders->setResolver('prm1', $resolver);
        self::assertEquals('resolved_val1', $this->requestHeaders->get('prm1'));
        // test that value is resolved only once
        self::assertEquals('resolved_val1', $this->requestHeaders->get('prm1'));
    }

    public function testGetLazyValueWhenResolverDoesNotSupportItAndInternalStorageIsNotInitialized()
    {
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with('val1')
            ->willReturn(false);
        $resolver->expects(self::never())
            ->method('resolve');

        $this->requestHeaders->setResolver('prm1', $resolver);
        self::assertEquals('val1', $this->requestHeaders->get('prm1'));
        // test that value is resolved only once
        self::assertEquals('val1', $this->requestHeaders->get('prm1'));
    }

    public function testToArrayWhenInternalStorageIsNotInitialized()
    {
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with('val1')
            ->willReturn(true);
        $resolver->expects(self::once())
            ->method('resolve')
            ->with('val1')
            ->willReturn('resolved_val1');

        $this->requestHeaders->setResolver('prm1', $resolver);

        self::assertEquals(
            ['prm1' => 'resolved_val1', 'prm-2' => 'val2'],
            array_intersect_key($this->requestHeaders->toArray(), ['prm1' => null, 'prm-2' => null])
        );
    }

    public function testCountWhenInternalStorageIsNotInitialized()
    {
        self::assertEquals(
            $this->request->headers->count(),
            $this->requestHeaders->count()
        );
    }

    public function testHasWhenInternalStorageIsInitialized()
    {
        $this->requestHeaders->set('prm1', 'new_val');
        self::assertTrue($this->requestHeaders->has('prm1'));
        self::assertTrue($this->requestHeaders->has('prm_2'));
    }

    public function testGetWhenInternalStorageIsInitialized()
    {
        $this->requestHeaders->set('prm1', 'new_val');
        self::assertEquals('new_val', $this->requestHeaders->get('prm1'));
        self::assertEquals('val2', $this->requestHeaders->get('prm_2'));
    }

    public function testGetLazyValueWhenInternalStorageIsInitialized()
    {
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with('new_val')
            ->willReturn(true);
        $resolver->expects(self::once())
            ->method('resolve')
            ->with('new_val')
            ->willReturn('resolved_new_val');

        $this->requestHeaders->set('prm1', 'new_val');
        $this->requestHeaders->setResolver('prm1', $resolver);
        self::assertEquals('resolved_new_val', $this->requestHeaders->get('prm1'));
        // test that value is resolved only once
        self::assertEquals('resolved_new_val', $this->requestHeaders->get('prm1'));
    }

    public function testGetLazyValueWhenResolverDoesNotSupportItAndInternalStorageIsInitialized()
    {
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with('new_val')
            ->willReturn(false);
        $resolver->expects(self::never())
            ->method('resolve');

        $this->requestHeaders->set('prm1', 'new_val');
        $this->requestHeaders->setResolver('prm1', $resolver);
        self::assertEquals('new_val', $this->requestHeaders->get('prm1'));
        // test that value is resolved only once
        self::assertEquals('new_val', $this->requestHeaders->get('prm1'));
    }

    public function testGetLazyValueShouldBeRecomputedWhenValueChanged()
    {
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::exactly(2))
            ->method('supports')
            ->willReturnMap([
                ['new_val1', true],
                ['new_val2', true]
            ]);
        $resolver->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnMap([
                ['new_val1', 'resolved_new_val1'],
                ['new_val2', 'resolved_new_val2']
            ]);

        $this->requestHeaders->setResolver('prm1', $resolver);

        $this->requestHeaders->set('prm1', 'new_val1');
        self::assertEquals('resolved_new_val1', $this->requestHeaders->get('prm1'));
        // test that value is resolved only once
        self::assertEquals('resolved_new_val1', $this->requestHeaders->get('prm1'));

        $this->requestHeaders->set('prm1', 'new_val2');
        self::assertEquals('resolved_new_val2', $this->requestHeaders->get('prm1'));
        // test that value is resolved only once
        self::assertEquals('resolved_new_val2', $this->requestHeaders->get('prm1'));
    }

    public function testToArrayWhenInternalStorageIsInitialized()
    {
        $resolver = $this->createMock(ParameterValueResolverInterface::class);
        $resolver->expects(self::once())
            ->method('supports')
            ->with('new_val')
            ->willReturn(true);
        $resolver->expects(self::once())
            ->method('resolve')
            ->with('new_val')
            ->willReturn('resolved_new_val');

        $this->requestHeaders->setResolver('prm1', $resolver);

        $this->requestHeaders->set('prm1', 'new_val');
        self::assertEquals(
            ['prm1' => 'resolved_new_val', 'prm-2' => 'val2'],
            array_intersect_key($this->requestHeaders->toArray(), ['prm1' => null, 'prm-2' => null])
        );
    }

    public function testCountWhenInternalStorageIsInitialized()
    {
        $this->requestHeaders->set('prm1', 'new_val');
        self::assertEquals(
            $this->request->headers->count(),
            $this->requestHeaders->count()
        );
    }

    public function testReplaceExistingParam()
    {
        $this->requestHeaders->set('PRM-2', 'new_val');
        self::assertEquals('new_val', $this->requestHeaders->get('prm_2'));
    }

    public function testAddNewParam()
    {
        $this->requestHeaders->set('PRM-3', 'new_val');
        self::assertEquals('new_val', $this->requestHeaders->get('prm_3'));
        self::assertEquals(
            ['prm1' => 'val1', 'prm-2' => 'val2', 'prm-3' => 'new_val'],
            array_intersect_key(
                $this->requestHeaders->toArray(),
                ['prm1' => null, 'prm-2' => null, 'prm-3' => null]
            )
        );
    }

    public function testRemoveParam()
    {
        $this->requestHeaders->remove('PRM-2');
        self::assertFalse($this->requestHeaders->has('prm_2'));
        self::assertNull($this->requestHeaders->get('prm_2'));
        self::assertEquals(
            ['prm1' => 'val1'],
            array_intersect_key(
                $this->requestHeaders->toArray(),
                ['prm1' => null, 'prm-2' => null]
            )
        );
    }

    public function testClear()
    {
        $this->requestHeaders->clear();
        self::assertEquals(
            [],
            array_intersect_key(
                $this->requestHeaders->toArray(),
                ['prm1' => null, 'prm-2' => null]
            )
        );
    }
}
