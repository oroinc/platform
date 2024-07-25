<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Captcha\ReCaptcha;

use GuzzleHttp\ClientInterface as HTTPClientInterface;
use GuzzleHttp\Psr7\Response;
use Oro\Bundle\FormBundle\Captcha\ReCaptcha\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ClientTest extends TestCase
{
    private HTTPClientInterface|MockObject $httpClient;
    private LoggerInterface|MockObject $logger;

    private Client $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HTTPClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client = new Client($this->httpClient, $this->logger, 'privateKey');
        $this->client->setExpectedHostname('test.com');
        $this->client->setScoreThreshold(0.5);
    }

    /**
     * @dataProvider verifyDataProvider
     */
    public function testVerify(array $response, bool $isVerified): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret' => 'privateKey',
                    'response' => 'responseValue',
                    'remoteip' => '127.0.0.1',
                    'version' => 'php_1.3.0'
                ]
            ])
            ->willReturn(new Response(200, [], json_encode($response)));

        $this->assertEquals($isVerified, $this->client->verify('responseValue', '127.0.0.1'));
    }

    public static function verifyDataProvider(): \Generator
    {
        yield [['success' => true, 'hostname' => 'test.com', 'score' => 0.9], true];
        yield [['success' => false, 'hostname' => 'test.com', 'score' => 0.9], false];
        yield [['success' => true, 'hostname' => 'test2.com', 'score' => 0.9], false];
        yield [['success' => true, 'hostname' => 'test.com', 'score' => 0.2], false];
    }

    public function testIsVerifiedReturnsFalseWhenExceptionIsThrown()
    {
        $exception = new \Exception('exception');
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Unable to verify reCAPTCHA',
                ['exception' => $exception]
            );

        $this->assertFalse($this->client->verify('responseValue', '127.0.0.1'));
    }
}
