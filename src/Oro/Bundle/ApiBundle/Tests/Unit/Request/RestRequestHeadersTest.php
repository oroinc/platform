<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\RestRequestHeaders;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestRequestHeadersTest extends \PHPUnit\Framework\TestCase
{
    /** @var Request */
    private $request;

    /** @var RestRequestHeaders */
    private $requestHeaders;

    protected function setUp(): void
    {
        $this->request = Request::create('http://test.com');
        $this->request->headers->remove('Accept');
        $this->request->headers->remove('Accept-Language');
        $this->request->headers->remove('Accept-Charset');
        $this->request->headers->remove('Accept-Encoding');
        $this->request->headers->set('prm1', 'val1');
        $this->request->headers->set('PRM-2', 'val2');

        $this->requestHeaders = new RestRequestHeaders($this->request);
    }

    public function testGetAcceptHeaderWhenItIsNotSpecifiedInRequest(): void
    {
        self::assertSame([], $this->requestHeaders->get('Accept'));
    }

    public function testGetAcceptHeaderWhenOneMediaTypeSpecifiedInRequest(): void
    {
        $this->request->headers->set('Accept', 'application/vnd.api+json');
        self::assertSame(
            ['application/vnd.api+json'],
            $this->requestHeaders->get('Accept')
        );
    }

    public function testGetAcceptHeader(): void
    {
        $this->request->headers->set('Accept', 'text/plain;q=0.5,text/html;q=0.9, application/vnd.api+json;ext=test');
        self::assertSame(
            ['application/vnd.api+json; ext=test', 'text/html', 'text/plain'],
            $this->requestHeaders->get('Accept')
        );
    }

    public function testGetAcceptLanguageHeaderWhenItIsNotSpecifiedInRequest(): void
    {
        self::assertSame([], $this->requestHeaders->get('Accept-Language'));
    }

    public function testGetAcceptLanguageHeader(): void
    {
        $this->request->headers->set('Accept-language', 'zh; q=0.8, en-us, en; q=0.6');
        self::assertSame(
            ['en_US', 'zh', 'en'],
            $this->requestHeaders->get('Accept-Language')
        );
    }

    public function testGetAcceptCharsetHeaderWhenItIsNotSpecifiedInRequest(): void
    {
        self::assertSame([], $this->requestHeaders->get('Accept-Charset'));
    }

    public function testGetAcceptCharsetHeader(): void
    {
        $this->request->headers->set('Accept-Charset', 'ISO-8859-1, US-ASCII, ISO-10646-UCS-2; q=0.6, UTF-8; q=0.8');
        self::assertSame(
            ['ISO-8859-1', 'US-ASCII', 'UTF-8', 'ISO-10646-UCS-2'],
            $this->requestHeaders->get('Accept-Charset')
        );
    }

    public function testGetAcceptEncodingHeaderWhenItIsNotSpecifiedInRequest(): void
    {
        self::assertSame([], $this->requestHeaders->get('Accept-Encoding'));
    }

    public function testGetAcceptEncodingHeader(): void
    {
        $this->request->headers->set('Accept-Encoding', 'gzip;q=0.4,deflate;q=0.9,compress;q=0.7');
        self::assertSame(['deflate', 'compress', 'gzip'], $this->requestHeaders->get('Accept-Encoding'));
    }

    public function testHasWhenInternalStorageIsNotInitialized(): void
    {
        self::assertTrue($this->requestHeaders->has('prm1'));
        self::assertTrue($this->requestHeaders->has('prm_2'));
    }

    public function testGetWhenInternalStorageIsNotInitialized(): void
    {
        self::assertEquals('val1', $this->requestHeaders->get('prm1'));
        self::assertEquals('val2', $this->requestHeaders->get('prm_2'));
    }

    public function testToArrayWhenInternalStorageIsNotInitialized(): void
    {
        self::assertEquals(
            ['prm1' => 'val1', 'prm-2' => 'val2'],
            array_intersect_key($this->requestHeaders->toArray(), ['prm1' => null, 'prm-2' => null])
        );
    }

    public function testCountWhenInternalStorageIsNotInitialized(): void
    {
        self::assertEquals(
            $this->request->headers->count(),
            $this->requestHeaders->count()
        );
    }

    public function testHasWhenInternalStorageIsInitialized(): void
    {
        $this->requestHeaders->set('prm1', 'new_val');
        self::assertTrue($this->requestHeaders->has('prm1'));
        self::assertTrue($this->requestHeaders->has('prm_2'));
    }

    public function testGetWhenInternalStorageIsInitialized(): void
    {
        $this->requestHeaders->set('prm1', 'new_val');
        self::assertEquals('new_val', $this->requestHeaders->get('prm1'));
        self::assertEquals('val2', $this->requestHeaders->get('prm_2'));
    }

    public function testToArrayWhenInternalStorageIsInitialized(): void
    {
        $this->requestHeaders->set('prm1', 'new_val');
        self::assertEquals(
            ['prm1' => 'new_val', 'prm-2' => 'val2'],
            array_intersect_key($this->requestHeaders->toArray(), ['prm1' => null, 'prm-2' => null])
        );
    }

    public function testCountWhenInternalStorageIsInitialized(): void
    {
        $this->requestHeaders->set('prm1', 'new_val');
        self::assertEquals(
            $this->request->headers->count(),
            $this->requestHeaders->count()
        );
    }

    public function testReplaceExistingParam(): void
    {
        $this->requestHeaders->set('PRM-2', 'new_val');
        self::assertEquals('new_val', $this->requestHeaders->get('prm_2'));
    }

    public function testAddNewParam(): void
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

    public function testRemoveParam(): void
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

    public function testClear(): void
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
