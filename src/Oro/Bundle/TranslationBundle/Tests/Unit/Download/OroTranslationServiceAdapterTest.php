<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Download;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Oro\Bundle\TranslationBundle\Download\OroTranslationServiceAdapter;
use Oro\Bundle\TranslationBundle\Exception\TranslationServiceAdapterException;
use Oro\Bundle\TranslationBundle\Test\TranslationArchiveGenerator;
use Oro\Component\Log\Test\LogAndThrowExceptionTestTrait;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversDefaultClass \Oro\Bundle\TranslationBundle\Download\OroTranslationServiceAdapter
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OroTranslationServiceAdapterTest extends TestCase
{
    use TempDirExtension;
    use LogAndThrowExceptionTestTrait;

    public const METRICS = [
        'uk_UA' => [
            'code' => 'uk_UA',
            'translationStatus' => 100,
            'lastBuildDate' => '2020-08-24T00:00:00+0300'
        ],
        'de_DE' => [
            'code' => 'de_DE',
            'altCode' => 'de',
            'translationStatus' => 90,
            'lastBuildDate' => '2020-10-03T23:59:59+0100'
        ],
        'fr_FR' => [
            'code' => 'fr_FR',
            'altCode' => 'fr',
            'translationStatus' => 80,
            'lastBuildDate' => '2020-07-14T00:00:00+0200'
        ],
        'fr_CA' => [
            'code' => 'fr_CA',
            'altCode' => 'fr',
            'translationStatus' => 70,
            'lastBuildDate' => '2020-07-01T23:59:59-0400'
        ],
    ];

    private const PACKAGES = ['PackageA', 'PackageB'];
    private const API_KEY = 'SOME-API-KEY';

    private Client $client;
    private OroTranslationServiceAdapter $adapter;

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->adapter = new OroTranslationServiceAdapter(
            $this->client,
            $this->logger,
            self::PACKAGES,
            ['apikey' => self::API_KEY]
        );
    }

    /**
     * @covers ::fetchTranslationMetrics
     * @covers ::jsonDecode
     * @covers ::request without a specified language
     */
    public function testFetchTranslationMetrics(): void
    {
        $response = new Response(200, [], Utils::jsonEncode(\array_values(self::METRICS)));
        $this->client->method('send')->willReturn($response);

        static::assertEquals(self::METRICS, $this->adapter->fetchTranslationMetrics());
    }

    /** @covers ::fetchTranslationMetrics */
    public function testFetchMetricsThrowsExceptionIfResponseStatusCodeIsNotOk(): void
    {
        $response = new Response(504);
        $this->client->method('send')->willReturn($response);

        $this->expectThrowErrorException(
            TranslationServiceAdapterException::class,
            'Translations service not available (status code: 504).',
            'Translations service not available (status code: {response_status_code}).',
            ['response_status_code' => 504]
        );

        $this->adapter->fetchTranslationMetrics();
    }

    /** @covers ::jsonDecode */
    public function testFetchMetricsThrowsExceptionIfReceivedNotJson(): void
    {
        $response = new Response(200, [], 'not JSON');
        $this->client->method('send')->willReturn($response);

        $this->expectThrowErrorException(
            TranslationServiceAdapterException::class,
            'Cannot decode the translation metrics response.',
            'Cannot decode the translation metrics response.',
            ['response_body_contents' => 'not JSON'],
            \GuzzleHttp\Exception\InvalidArgumentException::class
        );

        $this->adapter->fetchTranslationMetrics();
    }

    /** @covers ::fetchTranslationMetrics */
    public function testFetchMetricsThrowsExceptionIfReceivedNotArray(): void
    {
        /** @noinspection JsonEncodingApiUsageInspection */
        $response = new Response(200, [], \json_encode('not array'));
        $this->client->method('send')->willReturn($response);

        $this->expectThrowErrorException(
            TranslationServiceAdapterException::class,
            'Received malformed translation metrics response.',
            'Received malformed translation metrics response.',
            ['decoded_response' => 'not array']
        );

        $this->adapter->fetchTranslationMetrics();
    }

    /** @covers ::fetchTranslationMetrics */
    public function testFetchMetricsThrowsExceptionIfNoValidMetricsForAnyLanguage(): void
    {
        $incompleteMetrics = [
            ['code' => 'xyz', 'translationStatus' => 100],                               // no lastBuildDate
            ['code' => 'xyz', 'lastBuildDate' => '2020-12-31T20:08:24+0300'],            // no translationStatus
            ['translationStatus' => 100, 'lastBuildDate' => '2020-12-31T20:08:24+0300'], // no code
        ];
        $response = new Response(200, [], Utils::jsonEncode($incompleteMetrics));
        $this->client->method('send')->willReturn($response);

        $this->expectThrowErrorException(
            TranslationServiceAdapterException::class,
            'No valid translation metrics for any language.',
            'No valid translation metrics for any language.',
            ['decoded_response' => $incompleteMetrics]
        );

        $this->adapter->fetchTranslationMetrics();
    }

    /** @covers ::fetchTranslationMetrics */
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

        $response = new Response(200, [], Utils::jsonEncode(\array_values($originalMetrics)));
        $this->client->method('send')->willReturn($response);

        static::assertEquals($expectedMetrics, $this->adapter->fetchTranslationMetrics());
    }

    /**
     * @covers ::downloadLanguageTranslationsArchive
     * @covers ::getPackagesString
     * @covers ::request with a specified language
     */
    public function testDownloadLanguageTranslationsArchive(): void
    {
        $filePath = $this->getTempFile('download', 'tmp', 'translations.uk_UA.zip');
        $expectedUriString = 'https://translations.oroinc.com/api/download'
            . '?packages=PackageA,PackageB'
            . '&version=' . OroTranslationServiceAdapter::TRANSLATIONS_VERSION
            . '&language=uk-UA'
            . '&key=SOME-API-KEY'
        ;
        $this->client
            ->expects(static::once())
            ->method('send')
            ->with(
                static::callback(static fn (Request $request) => (string)$request->getUri() === $expectedUriString),
                ['sink' => $filePath]
            )
            ->willReturn(new Response(200));

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $filePath);
    }

    /** @covers ::downloadLanguageTranslationsArchive */
    public function testDownloadLanguageTranslationsArchiveThrowsExceptionIfCannotDeleteExistingFile(): void
    {
        $filePath = $this->getTempFile('download_' . \uniqid('', true), 'tmp', 'translations.uk_UA.zip');
        $tmpDir = \dirname($filePath);
        \touch($filePath);
        if (false === \chmod($filePath, 0444) || false === \chmod($tmpDir, 0555)) {
            static::fail('Cannot set the directory permissions necessary for this test.');
        }

        $this->expectThrowErrorException(
            TranslationServiceAdapterException::class,
            'Cannot overwrite the existing file "' . $filePath . '".',
            'Cannot overwrite the existing file "{actual_file_path}".',
            ['actual_file_path' => $filePath],
            null
        );
        try {
            $this->client->method('send')->willReturn(new Response(200));

            $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $filePath);
        } finally {
            \chmod($tmpDir, 0755);
            \chmod($filePath, 0744);
            \unlink($filePath);
        }
    }

    /** @covers ::downloadLanguageTranslationsArchive */
    public function testDownloadLanguageTranslationsArchiveThrowsExceptionIfResponseStatusCodeIsNotOk(): void
    {
        $this->client->method('send')->willReturn(new Response(504));
        $this->expectThrowErrorException(
            TranslationServiceAdapterException::class,
            'Failed to download translations for "uk-UA" (status code: 504).',
            'Failed to download translations for "{language_code}" (status code: {response_status_code}).',
            ['language_code' => 'uk-UA', 'response_status_code' => 504]
        );

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $this->getTempFile('download'));
    }

    /** @covers ::normalizeFilePath */
    public function testNormalizeFilePathFullReturnsAsIs(): void
    {
        $filePath = '/some/dir/file.zip';
        $this->client
            ->expects(static::once())
            ->method('send')
            ->with(
                static::anything(),
                ['sink' => $filePath]
            )
            ->willReturn(new Response(200));

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $filePath);
    }

    /** @covers ::normalizeFilePath */
    public function testNormalizeFilePathDirectoryAppendsDefaultFileAndExtension(): void
    {
        $originalFilePath = '/some/dir/';
        $expectedFilePath = '/some/dir/translations.zip';

        $this->client
            ->expects(static::once())
            ->method('send')
            ->with(
                static::anything(),
                ['sink' => $expectedFilePath]
            )
            ->willReturn(new Response(200));

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $originalFilePath);
    }

    /** @covers ::normalizeFilePath */
    public function testNormalizeFilePathDifferentExtensionAppendsExtension(): void
    {
        $originalFilePath = '/some/dir/file.ext';
        $expectedFilePath = '/some/dir/file.ext.zip';

        $this->client
            ->expects(static::once())
            ->method('send')
            ->with(
                static::anything(),
                ['sink' => $expectedFilePath]
            )
            ->willReturn(new Response(200));

        $this->adapter->downloadLanguageTranslationsArchive('uk_UA', $originalFilePath);
    }

    /** @covers ::extractTranslationsFromArchive */
    public function testExtractTranslationsFromArchive(): void
    {
        $filePath = $this->getTempFile('download', 'tmp', 'uk_UA.zip');
        $expectedTranslations = [
            'messages' => ['key1' => 'tr1', 'key2' => 'tr2'],
            'validation' => ['key3' => 'tr3', 'key4' => 'tr4'],
        ];
        TranslationArchiveGenerator::createTranslationsZip($filePath, 'uk_UA', $expectedTranslations);

        $targetDir = $this->getTempDir('extract_translations');

        $this->adapter->extractTranslationsFromArchive($filePath, $targetDir);
        $messagesPath = $targetDir . \DIRECTORY_SEPARATOR . 'messages.uk_UA.yml';
        $validationPath = $targetDir . \DIRECTORY_SEPARATOR . 'validation.uk_UA.yml';
        static::assertFileExists($messagesPath);
        static::assertFileExists($validationPath);
        $actualTranslations = [
            'messages' => Yaml::parseFile($messagesPath),
            'validation' => Yaml::parseFile($validationPath),
        ];
        static::assertEquals($expectedTranslations, $actualTranslations);
    }

    /** @covers ::extractTranslationsFromArchive */
    public function testExtractTranslationsFromArchiveThrowsExceptionIfCannotOpenZip(): void
    {
        $filePath = $this->getTempFile('download', 'tmp', 'uk_UA.zip');
        \file_put_contents($filePath, 'xxx'); // not a zip

        $targetDir = $this->getTempDir('extract_translations');

        $this->expectThrowErrorException(
            TranslationServiceAdapterException::class,
            'Cannot open the translation archive "' . $filePath . '".',
            'Cannot open the translation archive "{actual_file_path}".',
            ['actual_file_path' => $filePath]
        );

        $this->adapter->extractTranslationsFromArchive($filePath, $targetDir);
    }

    /** @covers ::extractTranslationsFromArchive */
    public function testExtractTranslationsFromArchiveThrowsExceptionIfExtractionFails(): void
    {
        $filePath = $this->getTempFile('download', 'tmp', 'uk_UA.zip');
        TranslationArchiveGenerator::createTranslationsZip(
            $filePath,
            'uk_UA',
            TranslationArchiveGenerator::SMALL_TRANSLATION_SET
        );

        $targetDir = $this->getTempDir('extract_translations');
        if (false === \chmod($targetDir, 0555)) {
            static::fail('Cannot set the directory permissions necessary for this test.');
        }

        $this->expectThrowErrorException(
            TranslationServiceAdapterException::class,
            'Failed to extract "' . $filePath . '" to "' . $targetDir . '".',
            'Failed to extract "{actual_file_path}" to "{directory_path_to_extract_to}".',
            ['actual_file_path' => $filePath, 'directory_path_to_extract_to' => $targetDir],
            null
        );

        try {
            $this->adapter->extractTranslationsFromArchive($filePath, $targetDir);
        } finally {
            \chmod($targetDir, 0755);
        }
    }

    /** @covers ::request */
    public function testRequestWithoutApiKey(): void
    {
        $adapter = new OroTranslationServiceAdapter(
            $this->client,
            $this->logger,
            self::PACKAGES,
            ['apikey' => '']
        );
        $expectedUriString = 'https://translations.oroinc.com/api/stats'
            . '?packages=PackageA,PackageB'
            . '&version=' . OroTranslationServiceAdapter::TRANSLATIONS_VERSION
        ;
        $response = new Response(200, [], Utils::jsonEncode(\array_values(self::METRICS)));
        $this->client
            ->expects(static::once())
            ->method('send')
            ->with(
                static::callback(static fn (Request $request) => (string)$request->getUri() === $expectedUriString),
            )
            ->willReturn($response);

        $adapter->fetchTranslationMetrics();
    }

    /** @covers ::request */
    public function testRequestApiKeyObfuscatedForLogging(): void
    {
        $response = new Response(200, [], Utils::jsonEncode(\array_values(self::METRICS)));
        $this->client->method('send')->willReturn($response);

        $expectedLogUri = 'https://translations.oroinc.com/api/stats'
            . '?packages=PackageA,PackageB'
            . '&version=' . OroTranslationServiceAdapter::TRANSLATIONS_VERSION
            . '&key=********'
        ;
        $expectedLogData = [
            'packages' => 'PackageA,PackageB',
            'version' => OroTranslationServiceAdapter::TRANSLATIONS_VERSION,
            'key' => '********'
        ];

        $this->logger->expects(static::once())
            ->method('info')
            ->with(
                'Requesting data from "{uri}".',
                ['uri' => $expectedLogUri, 'data' => $expectedLogData]
            );
        $this->adapter->fetchTranslationMetrics();
    }

    /** @covers ::request */
    public function testRequestLogsDebugStatusCode(): void
    {
        $this->client->method('send')->willReturn(new Response(567));

        $this->expectException(TranslationServiceAdapterException::class);
        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                'The translation service responded with status code {status_code}.',
                ['status_code' => 567]
            );

        $this->adapter->fetchTranslationMetrics();
    }

    /** @covers ::request */
    public function testRequestLogsWarningOnRequestGuzzleRequestException(): void
    {
        $request = new Request('GET', 'https://example.com');
        $response = new Response(567);
        $requestException = new RequestException('test message', $request, $response);
        $this->client->method('send')->willThrowException($requestException);

        $this->logger->expects(static::once())
            ->method('warning')
            ->with(
                'test message',
                ['exception' => $requestException]
            );
        $this->expectException(TranslationServiceAdapterException::class);

        $this->adapter->fetchTranslationMetrics();
    }

    /** @covers ::request */
    public function testRequestThrowsExceptionOnGenericGuzzleException(): void
    {
        $guzzleException = new \GuzzleHttp\Exception\InvalidArgumentException('test message');
        $this->client->method('send')->willThrowException($guzzleException);

        $this->expectThrowErrorException(
            TranslationServiceAdapterException::class,
            'Request to the translation service failed: test message.',
            'Request to the translation service failed: {original_message}.',
            ['original_message' => 'test message'],
            \get_class($guzzleException)
        );

        $this->adapter->fetchTranslationMetrics();
    }
}
