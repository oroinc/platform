<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Download;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Oro\Bundle\TranslationBundle\Download\OroTranslationServiceAdapter;
use Oro\Bundle\TranslationBundle\Exception\TranslationServiceAdapterException;
use Oro\Bundle\TranslationBundle\Exception\TranslationServiceInvalidResponseException;
use Oro\Bundle\TranslationBundle\Test\TranslationArchiveGenerator;
use Oro\Component\Testing\Logger\BufferingLogger;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\CsvFileLoader;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OroTranslationServiceAdapterTest extends TestCase
{
    use TempDirExtension;

    private const TRANSLATIONS_VERSION = '4.2.0';
    private const METRICS = [
        'uk_UA' => [
            'code'              => 'uk_UA',
            'translationStatus' => 100,
            'lastBuildDate'     => '2020-08-24T00:00:00+0300'
        ],
        'de_DE' => [
            'code'              => 'de_DE',
            'altCode'           => 'de',
            'translationStatus' => 90,
            'lastBuildDate'     => '2020-10-03T23:59:59+0100'
        ],
        'fr_FR' => [
            'code'              => 'fr_FR',
            'altCode'           => 'fr',
            'translationStatus' => 80,
            'lastBuildDate'     => '2020-07-14T00:00:00+0200'
        ],
        'fr_CA' => [
            'code'              => 'fr_CA',
            'altCode'           => 'fr',
            'translationStatus' => 70,
            'lastBuildDate'     => '2020-07-01T23:59:59-0400'
        ],
    ];
    private const PACKAGES = ['PackageA', 'PackageB'];
    private const API_KEY = 'SOME-API-KEY';

    /** @var Client|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var BufferingLogger|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var OroTranslationServiceAdapter */
    private $adapter;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->logger = new BufferingLogger();

        $this->adapter = new OroTranslationServiceAdapter(
            $this->client,
            $this->logger,
            self::PACKAGES,
            ['apikey' => self::API_KEY]
        );
    }

    private static function getRequestingStatsLog(bool $withoutApiKey = false): array
    {
        $expectedLogUri = 'https://translations.oroinc.com/api/stats'
            . '?packages=PackageA,PackageB'
            . '&version=' . self::TRANSLATIONS_VERSION;
        $expectedLogData = [
            'packages' => 'PackageA,PackageB',
            'version'  => self::TRANSLATIONS_VERSION
        ];
        if (!$withoutApiKey) {
            $expectedLogUri .= '&key=********';
            $expectedLogData['key'] = '********';
        }

        return [
            'info',
            'Requesting data from "{uri}".',
            ['uri' => $expectedLogUri, 'data' => $expectedLogData]
        ];
    }

    private static function getRequestingDownloadLog(): array
    {
        $expectedLogUri = 'https://translations.oroinc.com/api/download'
            . '?packages=PackageA,PackageB'
            . '&version=' . self::TRANSLATIONS_VERSION
            . '&language=uk-UA'
            . '&key=********';
        $expectedLogData = [
            'packages' => 'PackageA,PackageB',
            'version'  => self::TRANSLATIONS_VERSION,
            'key'      => '********',
            'language' => 'uk-UA'
        ];

        return [
            'info',
            'Requesting data from "{uri}".',
            ['uri' => $expectedLogUri, 'data' => $expectedLogData]
        ];
    }

    private static function getRequestFinishedLog(int $statusCode): array
    {
        return [
            'debug',
            'The translation service responded with status code {status_code}.',
            ['status_code' => $statusCode]
        ];
    }

    public function testFetchTranslationMetrics(): void
    {
        $response = new Response(200, [], json_encode(array_values(self::METRICS), JSON_THROW_ON_ERROR));
        $this->client->expects(self::once())
            ->method('send')
            ->willReturn($response);

        self::assertEquals(self::METRICS, $this->adapter->fetchTranslationMetrics());

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testFetchMetricsThrowsExceptionIfResponseStatusCodeIsNotOk(): void
    {
        $response = new Response(504);
        $this->client->expects(self::once())
            ->method('send')
            ->willReturn($response);

        $this->expectException(TranslationServiceAdapterException::class);
        $this->expectExceptionMessage('Translations service not available (status code: 504).');

        $this->adapter->fetchTranslationMetrics();

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(504),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testFetchMetricsThrowsExceptionIfReceivedNotJson(): void
    {
        $responseContent = 'not JSON';
        $response = new Response(200, [], 'not JSON');
        $this->client->expects(self::once())
            ->method('send')
            ->willReturn($response);

        $this->expectExceptionObject(new TranslationServiceInvalidResponseException(
            'Cannot decode the translation metrics response.',
            $responseContent
        ));

        $this->adapter->fetchTranslationMetrics();

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testFetchMetricsThrowsExceptionIfReceivedNotArray(): void
    {
        $responseContent = json_encode('not array', JSON_THROW_ON_ERROR);
        $response = new Response(200, [], json_encode('not array', JSON_THROW_ON_ERROR));
        $this->client->expects(self::once())
            ->method('send')
            ->willReturn($response);

        $this->expectExceptionObject(new TranslationServiceInvalidResponseException(
            'Received malformed translation metrics response.',
            $responseContent
        ));

        $this->adapter->fetchTranslationMetrics();

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testFetchMetricsThrowsExceptionIfNoValidMetricsForAnyLanguage(): void
    {
        $incompleteMetrics = [
            ['code' => 'xyz', 'translationStatus' => 100],                               // no lastBuildDate
            ['code' => 'xyz', 'lastBuildDate' => '2020-12-31T20:08:24+0300'],            // no translationStatus
            ['translationStatus' => 100, 'lastBuildDate' => '2020-12-31T20:08:24+0300'], // no code
        ];
        $responseContent = json_encode($incompleteMetrics, JSON_THROW_ON_ERROR);
        $response = new Response(200, [], $responseContent);
        $this->client->expects(self::once())
            ->method('send')
            ->willReturn($response);

        $this->expectExceptionObject(new TranslationServiceInvalidResponseException(
            'No valid translation metrics for any language.',
            $responseContent
        ));

        $this->adapter->fetchTranslationMetrics();

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testFetchMetricsUsesRealCodeAsAltCodeIfNotSet(): void
    {
        $expectedMetrics = self::METRICS;
        $originalMetrics = self::METRICS;

        unset($originalMetrics['fr_FR']['altCode']);
        $originalMetrics['fr_FR']['RealCode'] = 'abc'; // will be used as altCode is not set
        $expectedMetrics['fr_FR']['altCode'] = 'abc';

        $originalMetrics['fr_CA']['altCode'] = 'abc';
        $originalMetrics['fr_CA']['RealCode'] = 'xyz'; // should be ignored because altCode is already present
        $expectedMetrics['fr_CA']['altCode'] = 'abc';

        $response = new Response(200, [], json_encode(array_values($originalMetrics), JSON_THROW_ON_ERROR));
        $this->client->expects(self::once())
            ->method('send')
            ->willReturn($response);

        self::assertEquals($expectedMetrics, $this->adapter->fetchTranslationMetrics());

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testDownloadLanguageTranslationsArchive(): void
    {
        $filePath = $this->getTempFile('download', 'tmp', 'translations.uk_UA.zip');
        $this->client->expects(self::once())
            ->method('send')
            ->with(self::isInstanceOf(Request::class), ['sink' => $filePath])
            ->willReturnCallback(function (Request $request) {
                self::assertEquals(
                    'https://translations.oroinc.com/api/download'
                    . '?packages=PackageA,PackageB'
                    . '&version=' . self::TRANSLATIONS_VERSION
                    . '&language=uk-UA'
                    . '&key=SOME-API-KEY',
                    (string)$request->getUri()
                );

                return new Response(200);
            });

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $filePath);

        self::assertEquals(
            [
                self::getRequestingDownloadLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testDownloadLanguageTranslationsArchiveThrowsExceptionIfCannotDeleteExistingFile(): void
    {
        $filePath = $this->getTempFile('download_' . uniqid('', true), 'tmp', 'translations.uk_UA.zip');
        $tmpDir = dirname($filePath);
        touch($filePath);
        if (false === chmod($filePath, 0444) || false === chmod($tmpDir, 0555)) {
            self::fail('Cannot set the directory permissions necessary for this test.');
        }

        $this->expectException(TranslationServiceAdapterException::class);
        $this->expectExceptionMessage('Cannot overwrite the existing file "' . $filePath . '".');

        try {
            $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $filePath);
        } finally {
            chmod($tmpDir, 0755);
            chmod($filePath, 0744);
            unlink($filePath);
        }

        self::assertEquals(
            [
                self::getRequestingDownloadLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testDownloadLanguageTranslationsArchiveThrowsExceptionIfResponseStatusCodeIsNotOk(): void
    {
        $this->client->expects(self::once())
            ->method('send')
            ->willReturn(new Response(504));

        $this->expectException(TranslationServiceAdapterException::class);
        $this->expectExceptionMessage('Failed to download translations for "uk-UA" (status code: 504).');

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $this->getTempFile('download'));

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(504),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testNormalizeFilePathFullReturnsAsIs(): void
    {
        $filePath = '/some/dir/file.zip';
        $this->client->expects(self::once())
            ->method('send')
            ->with(self::anything(), ['sink' => $filePath])
            ->willReturn(new Response(200));

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $filePath);

        self::assertEquals(
            [
                self::getRequestingDownloadLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testNormalizeFilePathDirectoryAppendsDefaultFileAndExtension(): void
    {
        $originalFilePath = '/some/dir/';
        $expectedFilePath = '/some/dir/translations.zip';

        $this->client->expects(self::once())
            ->method('send')
            ->with(self::anything(), ['sink' => $expectedFilePath])
            ->willReturn(new Response(200));

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $originalFilePath);

        self::assertEquals(
            [
                self::getRequestingDownloadLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testNormalizeFilePathDifferentExtensionAppendsExtension(): void
    {
        $originalFilePath = '/some/dir/file.ext';
        $expectedFilePath = '/some/dir/file.ext.zip';

        $this->client->expects(self::once())
            ->method('send')
            ->with(self::anything(), ['sink' => $expectedFilePath])
            ->willReturn(new Response(200));

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $originalFilePath);

        self::assertEquals(
            [
                self::getRequestingDownloadLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testExtractTranslationsFromArchive(): void
    {
        $filePath = $this->getTempFile('download', 'tmp', 'uk_UA.zip');
        $expectedTranslations = [
            'messages'   => ['key1' => 'tr1', 'key2' => 'tr2'],
            'validation' => ['key3' => 'tr3', 'key4' => 'tr4'],
        ];
        TranslationArchiveGenerator::createTranslationsZip($filePath, 'uk_UA', $expectedTranslations);

        $targetDir = $this->getTempDir('extract_translations');

        $this->adapter->extractTranslationsFromArchive($filePath, $targetDir, 'uk_UA');
        $messagesPath = $targetDir . DIRECTORY_SEPARATOR . 'messages.uk_UA.csv';
        $validationPath = $targetDir . DIRECTORY_SEPARATOR . 'validation.uk_UA.csv';
        self::assertFileExists($messagesPath);
        self::assertFileExists($validationPath);
        $actualTranslations = [
            'messages'   => (new CsvFileLoader())->load($messagesPath, 'uk_UA', 'messages')->all('messages'),
            'validation' => (new CsvFileLoader())->load($validationPath, 'uk_UA', 'validation')->all('validation'),
        ];
        self::assertEquals($expectedTranslations, $actualTranslations);
    }

    public function testExtractTranslationsFromArchiveThrowsExceptionIfCannotOpenZip(): void
    {
        $filePath = $this->getTempFile('download', 'tmp', 'uk_UA.zip');
        file_put_contents($filePath, 'xxx'); // not a zip

        $targetDir = $this->getTempDir('extract_translations');

        $this->expectException(TranslationServiceAdapterException::class);
        $this->expectExceptionMessage('Cannot open the translation archive "' . $filePath . '".');

        $this->adapter->extractTranslationsFromArchive($filePath, $targetDir, 'uk_UA');
    }

    public function testExtractTranslationsFromArchiveThrowsExceptionIfExtractionFails(): void
    {
        $filePath = $this->getTempFile('download', 'tmp', 'uk_UA.zip');
        TranslationArchiveGenerator::createTranslationsZip(
            $filePath,
            'uk_UA',
            TranslationArchiveGenerator::SMALL_TRANSLATION_SET
        );

        $targetDir = $this->getTempDir('extract_translations');
        if (false === chmod($targetDir, 0555)) {
            self::fail('Cannot set the directory permissions necessary for this test.');
        }

        $this->expectException(TranslationServiceAdapterException::class);
        $this->expectExceptionMessage('Failed to extract "' . $filePath . '" to "' . $targetDir . '".');

        try {
            $this->adapter->extractTranslationsFromArchive($filePath, $targetDir, 'uk_UA');
        } finally {
            chmod($targetDir, 0755);
        }
    }

    public function testRequestWithoutApiKey(): void
    {
        $adapter = new OroTranslationServiceAdapter(
            $this->client,
            $this->logger,
            self::PACKAGES,
            ['apikey' => '']
        );
        $this->client->expects(self::once())
            ->method('send')
            ->with(self::isInstanceOf(Request::class), ['sink' => null])
            ->willReturnCallback(function (Request $request) {
                self::assertEquals(
                    'https://translations.oroinc.com/api/stats'
                    . '?packages=PackageA,PackageB'
                    . '&version=' . self::TRANSLATIONS_VERSION,
                    (string)$request->getUri()
                );

                return new Response(200, [], json_encode(array_values(self::METRICS), JSON_THROW_ON_ERROR));
            });

        $adapter->fetchTranslationMetrics();

        self::assertEquals(
            [
                self::getRequestingStatsLog(true),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testRequestApiKeyObfuscatedForLogging(): void
    {
        $response = new Response(200, [], json_encode(array_values(self::METRICS), JSON_THROW_ON_ERROR));
        $this->client->expects(self::once())
            ->method('send')
            ->willReturn($response);

        $this->adapter->fetchTranslationMetrics();

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(200),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testRequestLogsDebugStatusCode(): void
    {
        $this->client->expects(self::once())
            ->method('send')
            ->willReturn(new Response(567));

        $this->expectException(TranslationServiceAdapterException::class);
        $this->expectExceptionMessage('Translations service not available (status code: 567).');

        $this->adapter->fetchTranslationMetrics();

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(567),
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testRequestLogsWarningOnRequestGuzzleRequestException(): void
    {
        $request = new Request('GET', 'https://example.com');
        $response = new Response(567);
        $requestException = new RequestException('test message', $request, $response);
        $this->client->expects(self::once())
            ->method('send')
            ->willThrowException($requestException);

        $this->expectException(TranslationServiceAdapterException::class);
        $this->expectExceptionMessage('Translations service not available (status code: 567).');

        $this->adapter->fetchTranslationMetrics();

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
                self::getRequestFinishedLog(567),
                ['warning', 'test message', ['exception' => $requestException]],
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testRequestThrowsExceptionOnGenericGuzzleException(): void
    {
        $guzzleException = new InvalidArgumentException('test message');
        $this->client->expects(self::once())
            ->method('send')
            ->willThrowException($guzzleException);

        $this->expectException(TranslationServiceAdapterException::class);
        $this->expectExceptionMessage('Request to the translation service failed.');

        $this->adapter->fetchTranslationMetrics();

        self::assertEquals(
            [
                self::getRequestingStatsLog(),
            ],
            $this->logger->cleanLogs()
        );
    }
}
