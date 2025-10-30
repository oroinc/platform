<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Testing\Logger\BufferingLogger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ExternalFileFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const URL = 'http://example.org/image.png';
    private const DEFAULT_HTTP_OPTIONS = ['sample_key' => 'sample_value'];
    private const string REGEX_URL = '/^http:\/\/example\.org*/';

    private ClientInterface|\PHPUnit\Framework\MockObject\MockObject $httpClient;
    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;
    private LoggerInterface $logger;
    private ExternalFileFactory $factory;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->logger = new BufferingLogger();

        $this->factory = new ExternalFileFactory(
            $this->httpClient,
            self::DEFAULT_HTTP_OPTIONS,
            $this->logger,
        );

        $this->factory->setConfigManager($this->configManager);
    }

    private function getExpectedHttpOptions(): array
    {
        return self::DEFAULT_HTTP_OPTIONS + [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::ALLOW_REDIRECTS => true,
                RequestOptions::CONNECT_TIMEOUT => 30,
                RequestOptions::TIMEOUT => 30,
            ];
    }

    /**
     * @dataProvider createFromFileDataProvider
     */
    public function testCreateFromFile(File $file, ?ExternalFile $externalFile): void
    {
        self::assertEquals($externalFile, $this->factory->createFromFile($file));
    }

    public function createFromFileDataProvider(): array
    {
        return [
            'returns null if file has not externalUrl' => [
                'file' => new File(),
                'externalFile' => null,
            ],
            'returns ExternalFile if file has externalUrl' => [
                'file' => (new File())->setExternalUrl('http://example.org/image.png'),
                'externalFile' => new ExternalFile('http://example.org/image.png', '', 0, ''),
            ],
            'returns ExternalFile with original name if file has original name' => [
                'file' => (new File())
                    ->setExternalUrl('http://example.org/image.png')
                    ->setOriginalFilename('original-image.png')
                    ->setFileSize(4242)
                    ->setMimeType('image/png'),
                'externalFile' => new ExternalFile(
                    'http://example.org/image.png',
                    'original-image.png',
                    4242,
                    'image/png'
                ),
            ],
            'returns ExternalFile with regular name if file has not original name' => [
                'file' => (new File())
                    ->setExternalUrl('http://example.org/image.png')
                    ->setFilename('image.png')
                    ->setFileSize(4242)
                    ->setMimeType('image/png'),
                'externalFile' => new ExternalFile('http://example.org/image.png', 'image.png', 4242, 'image/png'),
            ],
        ];
    }

    public function testCreateFromUrlWhenRequestException(): void
    {
        $response = new Response(403);
        $exception = new RequestException(
            'Sample request exception',
            $this->createMock(RequestInterface::class),
            $response,
            new \RuntimeException('Sample Error')
        );
        $this->httpClient->expects(self::once())
            ->method('request')
            ->with('HEAD', self::URL, $this->getExpectedHttpOptions())
            ->willThrowException($exception);

        $this->expectExceptionObject(
            new ExternalFileNotAccessibleException(
                self::URL,
                'Forbidden',
                $exception,
                $response
            )
        );

        $this->factory->createFromUrl(self::URL);
    }

    public function testCreateFromUrlWhenRequestExceptionWithoutResponse(): void
    {
        $exception = new RequestException(
            'Sample request exception',
            $this->createMock(RequestInterface::class),
            null,
            new \RuntimeException('Sample Error'),
            ['error' => 'Protocol not supported']
        );
        $this->httpClient->expects(self::once())
            ->method('request')
            ->with('HEAD', self::URL, $this->getExpectedHttpOptions())
            ->willThrowException($exception);

        $this->expectExceptionObject(
            new ExternalFileNotAccessibleException(
                self::URL,
                'Protocol not supported',
                $exception
            )
        );

        $this->factory->createFromUrl(self::URL);
    }

    public function testCreateFromUrlWhenConnectException(): void
    {
        $exception = new ConnectException(
            'Sample connection exception',
            $this->createMock(RequestInterface::class),
            new \RuntimeException('Sample Error'),
            ['error' => 'Failed to resolve domain']
        );
        $this->httpClient->expects(self::once())
            ->method('request')
            ->with('HEAD', self::URL, $this->getExpectedHttpOptions())
            ->willThrowException($exception);

        $this->expectExceptionObject(
            new ExternalFileNotAccessibleException(
                self::URL,
                'Failed to resolve domain',
                $exception
            )
        );

        $this->factory->createFromUrl(self::URL);
    }

    public function testCreateFromUrlWhenGuzzleException(): void
    {
        $exception = new InvalidArgumentException('Sample error');
        $this->httpClient->expects(self::once())
            ->method('request')
            ->with('HEAD', self::URL, $this->getExpectedHttpOptions())
            ->willThrowException($exception);

        $this->expectExceptionObject(
            new ExternalFileNotAccessibleException(self::URL, $exception->getMessage(), $exception)
        );

        $this->factory->createFromUrl(self::URL);

        self::assertEquals(
            [
                [
                    'info',
                    sprintf(
                        'Failed to make a HEAD request when creating an external file for %s: %s',
                        self::URL,
                        $exception->getMessage()
                    ),
                    ['exception' => $exception],
                ],
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testCreateFromUrlWhenStatusCodeNot200(): void
    {
        $response = new Response(404);
        $this->httpClient->expects(self::once())
            ->method('request')
            ->with('HEAD', self::URL, $this->getExpectedHttpOptions())
            ->willReturn($response);

        $this->expectExceptionObject(
            new ExternalFileNotAccessibleException(
                self::URL,
                $response->getReasonPhrase(),
                null,
                $response
            )
        );

        $this->factory->createFromUrl(self::URL);
    }

    public function testCreateFromUrlWithMethodsFromConfig(): void
    {
        $externalFileDetailsHttpMethods = [
            [
                'regex' => self::REGEX_URL,
                'methods' => ['HEAD', 'GET'],
            ],
            [
                'regex' => "/^http:\/\/someexample.cm*/",
                'methods' => ['GET'],
            ],
        ];

        $errorResponse = new Response(400, ['Content-Disposition' => 'inline;filename=image.png']);
        $successResponse = new Response(200, ['Content-Disposition' => 'inline;filename=image.png']);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_attachment.external_file_details_http_methods')
            ->willReturn($externalFileDetailsHttpMethods);

        $this->httpClient->expects(self::exactly(2))
            ->method('request')
            ->withConsecutive(
                ['HEAD', self::URL, $this->getExpectedHttpOptions()],
                ['GET', self::URL, $this->getExpectedHttpOptions()]
            )
            ->willReturnOnConsecutiveCalls(
                $errorResponse,
                $successResponse
            );

        self::assertEquals(
            new ExternalFile(self::URL, 'image.png'),
            $this->factory->createFromUrl(self::URL)
        );
    }

    /**
     * @dataProvider createFromUrlDataProvider
     */
    public function testCreateFromUrl(
        ResponseInterface $response,
        ExternalFile $externalFile,
        ?array $externalFileDetailsHttpMethods = [],
        string $expectedHttpMethod = 'HEAD'
    ): void {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_attachment.external_file_details_http_methods')
            ->willReturn($externalFileDetailsHttpMethods);

        $this->httpClient->expects(self::once())
            ->method('request')
            ->with($expectedHttpMethod, self::URL, $this->getExpectedHttpOptions())
            ->willReturn($response);

        self::assertEquals(
            $externalFile,
            $this->factory->createFromUrl(self::URL)
        );
    }

    public function createFromUrlDataProvider(): array
    {
        return [
            'without headers' => ['response' => new Response(), 'externalFile' => new ExternalFile(self::URL)],
            'with filename' => [
                'response' => (new Response(200, ['Content-Disposition' => 'inline;filename=image.png'])),
                'externalFile' => new ExternalFile(self::URL, 'image.png'),
            ],
            'with filename in quotes' => [
                'response' => (new Response(200, ['Content-Disposition' => 'inline;filename=\'image.png\''])),
                'externalFile' => new ExternalFile(self::URL, 'image.png'),
            ],
            'with filename in double quotes' => [
                'response' => (new Response(200, ['Content-Disposition' => 'inline;filename="image.png"'])),
                'externalFile' => new ExternalFile(self::URL, 'image.png'),
            ],
            'with mime type' => [
                'response' => (new Response(
                    200,
                    ['Content-Disposition' => 'inline;filename="image.png"', 'Content-Type' => 'image/png']
                )),
                'externalFile' => new ExternalFile(self::URL, 'image.png', 0, 'image/png'),
            ],
            'with size' => [
                'response' => (new Response(
                    200,
                    [
                        'Content-Disposition' => 'inline;filename="image.png"',
                        'Content-Type' => 'image/png',
                        'Content-Length' => 4242,
                    ]
                )),
                'externalFile' => new ExternalFile(self::URL, 'image.png', 4242, 'image/png'),
            ],
            'with invalid size' => [
                'response' => (new Response(
                    200,
                    [
                        'Content-Disposition' => 'inline;filename="image.png"',
                        'Content-Type' => 'image/png',
                        'Content-Length' => 'invalid',
                    ]
                )),
                'externalFile' => new ExternalFile(self::URL, 'image.png', 0, 'image/png'),
            ],
            'with negative size' => [
                'response' => (new Response(
                    200,
                    [
                        'Content-Disposition' => 'inline;filename="image.png"',
                        'Content-Type' => 'image/png',
                        'Content-Length' => -42,
                    ]
                )),
                'externalFile' => new ExternalFile(self::URL, 'image.png', 0, 'image/png'),
            ],
            'with filename and get methods from config' => [
                'response' => (new Response(200, ['Content-Disposition' => 'inline;filename=image.png'])),
                'externalFile' => new ExternalFile(self::URL, 'image.png'),
                'externalFileDetailsHttpMethods' => [
                    [
                        'regex' => self::REGEX_URL,
                        'methods' => ['GET'],
                    ],
                ],
                'expectedHttpMethod' => 'GET',
            ],
            'with size and head method from config' => [
                'response' => (new Response(
                    200,
                    [
                        'Content-Disposition' => 'inline;filename="image.png"',
                        'Content-Type' => 'image/png',
                        'Content-Length' => 4242,
                    ]
                )),
                'externalFile' => new ExternalFile(self::URL, 'image.png', 4242, 'image/png'),
                'externalFileDetailsHttpMethods' => [
                    [
                        'regex' => self::REGEX_URL,
                        'methods' => ['HEAD', 'GET'],
                    ],
                    [
                        'regex' => "/^http:\/\/someexample.cm*/",
                        'methods' => ['GET'],
                    ],
                ],
                'expectedHttpMethod' => 'HEAD',
            ],
        ];
    }
}
