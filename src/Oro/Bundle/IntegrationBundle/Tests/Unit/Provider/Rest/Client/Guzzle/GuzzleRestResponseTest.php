<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class GuzzleRestResponseTest extends TestCase
{
    private const REQUEST_URL = 'http://test';

    private Response&MockObject $sourceResponse;
    private GuzzleRestResponse $response;

    #[\Override]
    protected function setUp(): void
    {
        $this->sourceResponse = $this->createMock(Response::class);

        $this->response = new GuzzleRestResponse($this->sourceResponse, self::REQUEST_URL);
    }

    public function testGetRequestUrl(): void
    {
        $this->assertEquals(self::REQUEST_URL, $this->response->getRequestUrl());
    }

    public function testGetBodyAsString(): void
    {
        $body = 'test';
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, $body);
        rewind($stream);

        $this->sourceResponse->expects(self::once())
            ->method('getBody')
            ->willReturn(new Stream($stream));

        $this->assertSame($body, $this->response->getBodyAsString());
    }

    public function testGetBodyAsStringWhenErrorOccurred(): void
    {
        $this->expectException(GuzzleRestException::class);
        $this->expectExceptionMessage('some error');

        $this->sourceResponse->expects(self::once())
            ->method('getBody')
            ->willThrowException(new \Exception('some error'));

        $this->response->getBodyAsString();
    }

    public function testGetStatusCode(): void
    {
        $statusCode = 400;
        $this->sourceResponse->expects(self::once())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $this->assertSame($statusCode, $this->response->getStatusCode());
    }

    public function testGetHeader(): void
    {
        $name = 'someHeader';
        $value = ['test'];
        $this->sourceResponse->expects(self::once())
            ->method('getHeader')
            ->with($name)
            ->willReturn($value);

        $this->assertSame($value, $this->response->getHeader($name));
    }

    public function testGetHeaders(): void
    {
        $values = ['test'];
        $this->sourceResponse->expects(self::once())
            ->method('getHeaders')
            ->willReturn($values);

        $this->assertSame($values, $this->response->getHeaders());
    }

    public function testHasHeader(): void
    {
        $name = 'someHeader';
        $this->sourceResponse->expects(self::once())
            ->method('hasHeader')
            ->with($name)
            ->willReturn(true);

        $this->assertTrue($this->response->hasHeader($name));
    }

    public function testGetReasonPhrase(): void
    {
        $value = 'test';
        $this->sourceResponse->expects(self::once())
            ->method('getReasonPhrase')
            ->willReturn($value);

        $this->assertSame($value, $this->response->getReasonPhrase());
    }

    /**
     * @dataProvider isClientErrorDataProvider
     */
    public function testIsClientError(int $statusCode, bool $result): void
    {
        $this->sourceResponse->expects(self::atLeastOnce())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $this->assertSame($result, $this->response->isClientError());
    }

    public function isClientErrorDataProvider(): array
    {
        return [
            [200, false],
            [399, false],
            [400, true],
            [499, true],
            [500, false],
            [600, false]
        ];
    }

    /**
     * @dataProvider isServerErrorDataProvider
     */
    public function testIsServerError(int $statusCode, bool $result): void
    {
        $this->sourceResponse->expects(self::atLeastOnce())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $this->assertSame($result, $this->response->isServerError());
    }

    public function isServerErrorDataProvider(): array
    {
        return [
            [300, false],
            [499, false],
            [500, true],
            [599, true],
            [600, false],
            [700, false]
        ];
    }

    /**
     * @dataProvider isErrorDataProvider
     */
    public function testIsError(int $statusCode, bool $result): void
    {
        $this->sourceResponse->expects(self::atLeastOnce())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $this->assertSame($result, $this->response->isError());
    }

    public function isErrorDataProvider(): array
    {
        return [
            [200, false],
            [399, false],
            [400, true],
            [499, true],
            [500, true],
            [599, true],
            [600, false],
            [700, false]
        ];
    }

    /**
     * @dataProvider isSuccessfulDataProvider
     */
    public function testIsSuccessful(int $statusCode, bool $result): void
    {
        $this->sourceResponse->expects(self::atLeastOnce())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $this->assertSame($result, $this->response->isSuccessful());
    }

    public function isSuccessfulDataProvider(): array
    {
        return [
            [100, false],
            [199, false],
            [200, true],
            [299, true],
            [300, false],
            [399, false],
            [400, false],
            [499, false],
            [500, false],
            [599, false],
        ];
    }

    /**
     * @dataProvider isInformationalDataProvider
     */
    public function testIsInformational(int $statusCode, bool $result): void
    {
        $this->sourceResponse->expects(self::atLeastOnce())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $this->assertSame($result, $this->response->isInformational());
    }

    public function isInformationalDataProvider(): array
    {
        return [
            [100, true],
            [199, true],
            [200, false],
            [300, false],
        ];
    }

    /**
     * @dataProvider isRedirectDataProvider
     */
    public function testIsRedirect(int $statusCode, bool $result): void
    {
        $this->sourceResponse->expects(self::atLeastOnce())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $this->assertSame($result, $this->response->isRedirect());
    }

    public function isRedirectDataProvider(): array
    {
        return [
            [100, false],
            [299, false],
            [300, true],
            [399, true],
            [400, false],
            [500, false]
        ];
    }

    public function testJson(): void
    {
        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, '{"key": "val"}');
        rewind($stream);

        $this->sourceResponse->expects(self::once())
            ->method('getBody')
            ->willReturn(new Stream($stream));

        $this->assertSame(['key' => 'val'], $this->response->json());
    }

    public function testGetSourceResponse(): void
    {
        $this->assertEquals($this->sourceResponse, $this->response->getSourceResponse());
    }
}
