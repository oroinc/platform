<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Download;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Utils;
use Oro\Bundle\TranslationBundle\Exception\TranslationServiceAdapterException;
use Oro\Component\Log\LogAndThrowExceptionTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Adapter to download translations from Oro's translations server.
 */
final class OroTranslationServiceAdapter implements TranslationServiceAdapterInterface
{
    use LogAndThrowExceptionTrait;

    public const TRANSLATIONS_VERSION = '4.2.0';

    private const BASE_URI = 'https://translations.oroinc.com/api/';
    private ClientInterface $client;
    private LoggerInterface $logger;
    private string $apiKey;
    private array $translationPackageNames;

    /**
     * @param ClientInterface $guzzleHttpClient
     * @param LoggerInterface $logger
     * @param string[] $translationPackageNames
     * @param array $translationServiceCredentials empty array or ['apikey' => 'your_API_key_here']
     */
    public function __construct(
        ClientInterface $guzzleHttpClient,
        LoggerInterface $logger,
        array $translationPackageNames,
        array $translationServiceCredentials
    ) {
        $this->client = $guzzleHttpClient;
        $this->logger = $logger;
        $this->translationPackageNames = $translationPackageNames;
        $this->apiKey = $translationServiceCredentials['apikey'] ?? '';
    }

    public function fetchTranslationMetrics(): array
    {
        $response = $this->request('stats');

        if (200 !== $response->getStatusCode()) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'Translations service not available (status code: {response_status_code}).',
                ['response_status_code' => $response->getStatusCode()]
            );
        }

        $result = $this->jsonDecode($response);

        if (!\is_array($result)) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'Received malformed translation metrics response.',
                ['decoded_response' => $result]
            );
        }

        $filtered = \array_filter(
            $result,
            static fn ($item) => isset($item['code'], $item['translationStatus'], $item['lastBuildDate'])
        );

        if (empty($filtered)) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'No valid translation metrics for any language.',
                ['decoded_response' => $result]
            );
        }

        $normalized = \array_map(
            static function ($item) {
                if (isset($item['RealCode'])) {
                    if (!isset($item['altCode'])) {
                        $item['altCode'] = $item['RealCode'];
                    }
                    unset($item['RealCode']);
                }
                return $item;
            },
            $filtered
        );

        $organizedByLanguage = [];
        foreach ($normalized as $languageMetrics) {
            $organizedByLanguage[$languageMetrics['code']] = $languageMetrics;
        }

        return $organizedByLanguage;
    }

    /**
     * {@inheritdoc}
     *
     * All dashes used as the language-locality separators in language code will be treated as underscores
     * (e.g. "en-US" is treated as "en_US").
     *
     * @throws TranslationServiceAdapterException if the provided path is an existing file that cannot be overwritten,
     *                        or if the translation service returns other than 200 HTTP_OK response.
     */
    public function downloadLanguageTranslationsArchive(
        string $languageCode,
        string $pathToSaveDownloadedArchive
    ): void {
        $actualFilePath = $this->normalizeFilePath($pathToSaveDownloadedArchive);

        try {
            if (\file_exists($actualFilePath) && false === \unlink($actualFilePath)) {
                $this->throwErrorException(
                    TranslationServiceAdapterException::class,
                    'Cannot overwrite the existing file "{actual_file_path}".',
                    ['actual_file_path' => $actualFilePath]
                );
            }
        } catch (\Throwable $e) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'Cannot overwrite the existing file "{actual_file_path}".',
                ['actual_file_path' => $actualFilePath],
                $e
            );
        }

        $languageCode = \str_replace('_', '-', $languageCode);
        $result  = $this->request('download', $languageCode, $actualFilePath);

        if (200 !== $result->getStatusCode()) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'Failed to download translations for "{language_code}" (status code: {response_status_code}).',
                ['language_code' => $languageCode, 'response_status_code' => $result->getStatusCode()]
            );
        }
    }

    /**
     * @throws TranslationServiceAdapterException if cannot extract data from the archive
     */
    public function extractTranslationsFromArchive(string $pathToArchive, string $directoryPathToExtractTo): void
    {
        if (!\extension_loaded('zip')) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'PHP zip extension is required - https://php.net/manual/zip.installation.php'
            );
        }

        $actualFilePath = $this->normalizeFilePath($pathToArchive);

        $zip = new \ZipArchive();
        $res = $zip->open($actualFilePath);

        if (true !== $res) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'Cannot open the translation archive "{actual_file_path}".',
                ['actual_file_path' => $actualFilePath]
            );
        }

        try {
            if (false === $zip->extractTo($directoryPathToExtractTo)) {
                $this->throwErrorException(
                    TranslationServiceAdapterException::class,
                    'Failed to extract "{actual_file_path}" to "{directory_path_to_extract_to}".',
                    ['actual_file_path' => $actualFilePath, 'directory_path_to_extract_to' => $directoryPathToExtractTo]
                );
            }
        } catch (\Throwable $e) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'Failed to extract "{actual_file_path}" to "{directory_path_to_extract_to}".',
                ['actual_file_path' => $actualFilePath, 'directory_path_to_extract_to' => $directoryPathToExtractTo],
                $e
            );
        }

        try {
            if (false === $zip->close()) {
                $this->throwErrorException(
                    TranslationServiceAdapterException::class,
                    'Failed to close the translation archive "{actual_file_path}".',
                    ['actual_file_path' => $actualFilePath]
                );
            }
        } catch (\Throwable $e) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'Failed to close the translation archive "{actual_file_path}".',
                ['actual_file_path' => $actualFilePath],
                $e
            );
        }
    }

    private function request(string $uri, ?string $language = null, ?string $outputFilePath = null): ResponseInterface
    {
        $data = [
            'packages' => $this->getPackagesString(),
            'version' => self::TRANSLATIONS_VERSION
        ];
        if (null !== $language) {
            $data['language'] = $language;
        }

        $logData = $data;

        if ('' !== $this->apiKey) {
            $data['key'] = $this->apiKey;
            $logData['key'] = '********';
        }

        $logUri = Uri::withQueryValues(new Uri(self::BASE_URI . $uri), $logData);
        $this->logger->info('Requesting data from "{uri}".', ['uri' => (string)$logUri, 'data' => $logData]);

        $requestUri = Uri::withQueryValues(new Uri(self::BASE_URI . $uri), $data);
        $request = new Request('GET', $requestUri);

        try {
            $response = $this->client->send($request, ['sink' => $outputFilePath]);
            $this->logger->debug(
                'The translation service responded with status code {status_code}.',
                ['status_code' => $response->getStatusCode()]
            );
        } catch (RequestException $e) {
            $this->logger->warning($e->getMessage(), ['exception' => $e]);
            $response = $e->getResponse();
        } catch (GuzzleException $e) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'Request to the translation service failed: {original_message}.',
                ['original_message' => $e->getMessage()],
                $e
            );
        }

        /** @noinspection PhpUndefinedVariableInspection */
        return $response;
    }

    private function getPackagesString(): string
    {
        \sort($this->translationPackageNames);
        return \implode(',', $this->translationPackageNames);
    }

    /**
     * @return array|bool|float|int|object|string|null
     *
     * @throws TranslationServiceAdapterException if the response contents cannot be decoded
     */
    private function jsonDecode(ResponseInterface $response)
    {
        $responseBodyContents = $response->getBody()->getContents();

        try {
            $result = Utils::jsonDecode($responseBodyContents, true);
        } catch (\GuzzleHttp\Exception\InvalidArgumentException $e) {
            $this->throwErrorException(
                TranslationServiceAdapterException::class,
                'Cannot decode the translation metrics response.',
                ['response_body_contents' => $responseBodyContents],
                $e
            );
        }

        /** @noinspection PhpUndefinedVariableInspection */
        return $result;
    }

    /**
     * Appends the proper filename extension. If the provided path looks like a directory, uses 'translations' as name.
     */
    private function normalizeFilePath(string $filePath): string
    {
        return '.zip' === \substr($filePath, -4)
            ? $filePath
            : $filePath . (\DIRECTORY_SEPARATOR === \substr($filePath, -1) ? 'translations' : '') . '.zip';
    }
}
