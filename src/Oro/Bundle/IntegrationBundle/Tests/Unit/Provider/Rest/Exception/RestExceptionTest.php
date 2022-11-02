<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Exception;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

class RestExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider exceptionDataProvider
     */
    public function testCreateFromResponseWorks(
        string $expectedMessage,
        ?string $message,
        bool $isClientError,
        bool $isServerError,
        int $statusCode,
        string $reasonPhrase
    ) {
        $previous = new \Exception();

        $response = $this->createMock(RestResponseInterface::class);

        if ($isClientError) {
            $response->expects($this->once())
                ->method('isClientError')
                ->willReturn(true);
        }

        if ($isServerError) {
            $response->expects($this->once())
                ->method('isServerError')
                ->willReturn(true);
        }

        $response->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn($statusCode);

        $response->expects($this->once())
            ->method('getReasonPhrase')
            ->willReturn($reasonPhrase);

        $exception = RestException::createFromResponse($response, $message, $previous);

        $this->assertInstanceOf(RestException::class, $exception);
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($response, $exception->getResponse());
        $this->assertEquals($statusCode, $exception->getCode());
        $this->assertEquals($expectedMessage, $exception->getMessage());
    }

    public function exceptionDataProvider(): array
    {
        return [
            'client error' => [
                'expectedMessage' => 'Client error response' . PHP_EOL . '[status code] 401' . PHP_EOL
                    . '[reason phrase] Unauthorized',
                'message' => null,
                'isClientError' => true,
                'isServerError' => false,
                'statusCode' => 401,
                'reasonPhrase' => 'Unauthorized',
            ],
            'server error' => [
                'expectedMessage' => 'Server error response' . PHP_EOL . '[status code] 500' . PHP_EOL
                    . '[reason phrase] Internal Server Error',
                'message' => null,
                'isClientError' => false,
                'isServerError' => true,
                'statusCode' => 500,
                'reasonPhrase' => 'Internal Server Error',
            ],
            'other error' => [
                'expectedMessage' => 'Unsuccessful response' . PHP_EOL . '[status code] 304' . PHP_EOL
                    . '[reason phrase] Not Modified',
                'message' => null,
                'isClientError' => false,
                'isServerError' => false,
                'statusCode' => 304,
                'reasonPhrase' => 'Not Modified',
            ],
            'custom message' => [
                'expectedMessage' => 'Client error response: Can\'t create user' . PHP_EOL . '[status code] 400'
                    . PHP_EOL . '[reason phrase] Bad request',
                'message' => 'Can\'t create user',
                'isClientError' => true,
                'isServerError' => false,
                'statusCode' => 400,
                'reasonPhrase' => 'Bad request',
            ],
        ];
    }
}
