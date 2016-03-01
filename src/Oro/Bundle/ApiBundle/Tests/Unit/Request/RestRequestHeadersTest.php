<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;
use Symfony\Component\HttpFoundation\Request;

class RestRequestHeadersTest extends \PHPUnit_Framework_TestCase
{
    /** @var Request */
    protected $request;

    /** @var RestRequestHeaders */
    protected $requestHeaders;

    protected function setUp()
    {
        $this->request = Request::create('http://test.com');
        $this->request->headers->set('prm1', 'val1');
        $this->request->headers->set('PRM-2', 'val2');

        $this->requestHeaders = new RestRequestHeaders($this->request);
    }

    public function testHasWhenInternalStorageIsNotInitialized()
    {
        $this->assertTrue($this->requestHeaders->has('prm1'));
        $this->assertTrue($this->requestHeaders->has('prm_2'));
    }

    public function testGetWhenInternalStorageIsNotInitialized()
    {
        $this->assertEquals('val1', $this->requestHeaders->get('prm1'));
        $this->assertEquals('val2', $this->requestHeaders->get('prm_2'));
    }

    public function testToArrayWhenInternalStorageIsNotInitialized()
    {
        $this->assertEquals(
            ['prm1' => 'val1', 'prm-2' => 'val2'],
            array_intersect_key($this->requestHeaders->toArray(), ['prm1' => null, 'prm-2' => null])
        );
    }

    public function testCountWhenInternalStorageIsNotInitialized()
    {
        $this->assertEquals(
            $this->request->headers->count(),
            $this->requestHeaders->count()
        );
    }

    public function testHasWhenInternalStorageIsInitialized()
    {
        $this->requestHeaders->set('prm1', 'new_val');
        $this->assertTrue($this->requestHeaders->has('prm1'));
        $this->assertTrue($this->requestHeaders->has('prm_2'));
    }

    public function testGetWhenInternalStorageIsInitialized()
    {
        $this->requestHeaders->set('prm1', 'new_val');
        $this->assertEquals('new_val', $this->requestHeaders->get('prm1'));
        $this->assertEquals('val2', $this->requestHeaders->get('prm_2'));
    }

    public function testToArrayWhenInternalStorageIsInitialized()
    {
        $this->requestHeaders->set('prm1', 'new_val');
        $this->assertEquals(
            ['prm1' => 'new_val', 'prm-2' => 'val2'],
            array_intersect_key($this->requestHeaders->toArray(), ['prm1' => null, 'prm-2' => null])
        );
    }

    public function testCountWhenInternalStorageIsInitialized()
    {
        $this->requestHeaders->set('prm1', 'new_val');
        $this->assertEquals(
            $this->request->headers->count(),
            $this->requestHeaders->count()
        );
    }

    public function testReplaceExistingParam()
    {
        $this->requestHeaders->set('PRM-2', 'new_val');
        $this->assertEquals('new_val', $this->requestHeaders->get('prm_2'));
    }

    public function testAddNewParam()
    {
        $this->requestHeaders->set('PRM-3', 'new_val');
        $this->assertEquals('new_val', $this->requestHeaders->get('prm_3'));
        $this->assertEquals(
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
        $this->assertFalse($this->requestHeaders->has('prm_2'));
        $this->assertNull($this->requestHeaders->get('prm_2'));
        $this->assertEquals(
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
        $this->assertEquals(
            [],
            array_intersect_key(
                $this->requestHeaders->toArray(),
                ['prm1' => null, 'prm-2' => null]
            )
        );
    }
}
