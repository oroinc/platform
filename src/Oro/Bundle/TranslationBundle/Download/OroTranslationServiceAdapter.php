<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Download;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Oro\Bundle\TranslationBundle\Exception\TranslationServiceAdapterException;
use Oro\Bundle\TranslationBundle\Exception\TranslationServiceInvalidResponseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Yaml;

/**
 * Adapter to download translations from Oro's translations server.
 */
final class OroTranslationServiceAdapter implements TranslationServiceAdapterInterface
{
    private const TRANSLATIONS_VERSION = '4.2.0';

    private const BASE_URI = 'https://translations.oroinc.com/api/';
    private ClientInterface $client;
    private LoggerInterface $logger;
    private string $apiKey;
    private array $translationPackageNames;

    /**
     * @param ClientInterface $guzzleHttpClient
     * @param LoggerInterface $logger
     * @param string[]        $translationPackageNames
     * @param array           $translationServiceCredentials empty array or ['apikey' => 'your_API_key_here']
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

    /**
     * {@inheritDoc}
     */
    public function fetchTranslationMetrics(): array
    {
        $response = $this->request('stats');
        if (200 !== $response->getStatusCode()) {
            throw new TranslationServiceAdapterException(
                sprintf('Translations service not available (status code: %d).', $response->getStatusCode())
            );
        }

        $data = $this->decodeStatsResponse($response);
        $normalized = array_map(
            static function ($item) {
                if (isset($item['RealCode'])) {
                    if (!isset($item['altCode'])) {
                        $item['altCode'] = $item['RealCode'];
                    }
                    unset($item['RealCode']);
                }

                return $item;
            },
            $data
        );

        $organizedByLanguage = [];
        foreach ($normalized as $languageMetrics) {
            $organizedByLanguage[$languageMetrics['code']] = $languageMetrics;
        }

        return $organizedByLanguage;
    }

    /**
     * {@inheritDoc}
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

        $isRemoveFileFailed = false;
        try {
            if (file_exists($actualFilePath) && false === unlink($actualFilePath)) {
                $isRemoveFileFailed = true;
            }
        } catch (\Throwable $e) {
            throw self::createOverwriteExistingFileException($actualFilePath, $e);
        }
        if ($isRemoveFileFailed) {
            throw self::createOverwriteExistingFileException($actualFilePath);
        }

        $languageCode = str_replace('_', '-', $languageCode);
        $result = $this->request('download', $languageCode, $actualFilePath);
        if (200 !== $result->getStatusCode()) {
            throw new TranslationServiceAdapterException(
                sprintf(
                    'Failed to download translations for "%s" (status code: %d).',
                    $languageCode,
                    $result->getStatusCode()
                )
            );
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws TranslationServiceAdapterException if cannot extract data from the archive
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function extractTranslationsFromArchive(
        string $pathToArchive,
        string $directoryPathToExtractTo,
        string $languageCode
    ): void {
        if (!\extension_loaded('zip')) {
            throw new TranslationServiceAdapterException(
                'PHP zip extension is required - https://php.net/manual/zip.installation.php'
            );
        }

        $actualFilePath = $this->normalizeFilePath($pathToArchive);

        $zip = new \ZipArchive();
        $res = $zip->open($actualFilePath);

        if (true !== $res) {
            throw new TranslationServiceAdapterException(
                sprintf('Cannot open the translation archive "%s".', $actualFilePath)
            );
        }

        $yamlParser = new YamlParser();
        $allMessages = [];

        $isExtractFailed = false;
        try {
            for ($i = 0; $i < $zip->count(); $i++) {
                $filename = $zip->getNameIndex($i);
                if (\str_starts_with($filename, '/')) {
                    continue;
                }

                // "OroAuthorizeNetBundle/translations/jsmessages.es_ES.yml"
                $pathParts = \pathinfo(\substr($filename, 0, -1 * \strlen('.yml')));

                $localeCode = $pathParts['extension'];
                if ($localeCode !== $languageCode) {
                    continue;
                }

                $domain = $pathParts['filename'];
                if (\in_array($domain, ['OroFilterBundle', '_undefined'])) {
                    continue;
                }

                $messages = $yamlParser->parse($zip->getFromIndex($i), Yaml::PARSE_CONSTANT);
                if (\is_array($messages)) {
                    foreach ($messages as $key => $value) {
                        $allMessages[$localeCode][$domain][$key] = $value;
                    }
                }
            }

            $csvDelimiter = ';';
            $csvEnclosure = '"';
            $csvEscape = '\\';

            foreach ($allMessages as $locale => $domainMessages) {
                foreach ($domainMessages as $domain => $messages) {
                    $csvFile = new \SplFileObject(
                        $directoryPathToExtractTo . DIRECTORY_SEPARATOR . $domain . '.' . $locale . '.csv',
                        'w'
                    );
                    foreach ($messages as $key => $value) {
                        $csvFile->fputcsv([$key, $value], $csvDelimiter, $csvEnclosure, $csvEscape);
                    }
                }
            }
        } catch (\Throwable $e) {
            throw self::createExtractException($actualFilePath, $directoryPathToExtractTo, $e);
        }
        if ($isExtractFailed) {
            throw self::createExtractException($actualFilePath, $directoryPathToExtractTo);
        }

        $isCloseFailed = false;
        try {
            if (false === $zip->close()) {
                $isCloseFailed = true;
            }
        } catch (\Throwable $e) {
            throw self::createCloseTranslationArchiveException($actualFilePath, $e);
        }
        if ($isCloseFailed) {
            throw self::createCloseTranslationArchiveException($actualFilePath);
        }
    }

    private function request(string $uri, ?string $language = null, ?string $outputFilePath = null): ResponseInterface
    {
        $data = [
            'packages' => $this->getPackagesString(),
            'version'  => self::TRANSLATIONS_VERSION
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

        $request = new Request('GET', Uri::withQueryValues(new Uri(self::BASE_URI . $uri), $data));
        try {
            $response = $this->client->send($request, ['sink' => $outputFilePath]);
            $this->logger->debug(
                'The translation service responded with status code {status_code}.',
                ['status_code' => $response->getStatusCode()]
            );

            return $response;
        } catch (RequestException $e) {
            $this->logger->warning($e->getMessage(), ['exception' => $e]);

            return $e->getResponse();
        } catch (GuzzleException $e) {
            throw new TranslationServiceAdapterException('Request to the translation service failed.', $e);
        }
    }

    private function getPackagesString(): string
    {
        sort($this->translationPackageNames);

        return implode(',', $this->translationPackageNames);
    }

    /**
     * @throws TranslationServiceInvalidResponseException if a response of a translation service request
     *                                                    cannot be decoded or return invalid metrics
     */
    private function decodeStatsResponse(ResponseInterface $response): array
    {
        $responseBodyContents = $response->getBody()->getContents();
        try {
            $decodedData = json_decode($responseBodyContents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            throw new TranslationServiceInvalidResponseException(
                'Cannot decode the translation metrics response.',
                $responseBodyContents,
                $e
            );
        }
        if (!\is_array($decodedData)) {
            throw new TranslationServiceInvalidResponseException(
                'Received malformed translation metrics response.',
                $responseBodyContents
            );
        }

        $filteredData = array_filter(
            $decodedData,
            static fn ($item) => isset($item['code'], $item['translationStatus'], $item['lastBuildDate'])
        );
        if (!$filteredData) {
            throw new TranslationServiceInvalidResponseException(
                'No valid translation metrics for any language.',
                $responseBodyContents
            );
        }

        return $filteredData;
    }

    /**
     * Appends the proper filename extension. If the provided path looks like a directory, uses 'translations' as name.
     */
    private function normalizeFilePath(string $filePath): string
    {
        if (str_ends_with($filePath, '.zip')) {
            return $filePath;
        }

        return $filePath . (str_ends_with($filePath, DIRECTORY_SEPARATOR) ? 'translations' : '') . '.zip';
    }

    private static function createOverwriteExistingFileException(
        string $actualFilePath,
        \Throwable $previous = null
    ): TranslationServiceAdapterException {
        return new TranslationServiceAdapterException(
            sprintf('Cannot overwrite the existing file "%s".', $actualFilePath),
            $previous
        );
    }

    private static function createExtractException(
        string $actualFilePath,
        string $directoryPathToExtractTo,
        \Throwable $previous = null
    ): TranslationServiceAdapterException {
        return new TranslationServiceAdapterException(
            sprintf('Failed to extract "%s" to "%s".', $actualFilePath, $directoryPathToExtractTo),
            $previous
        );
    }

    private static function createCloseTranslationArchiveException(
        string $actualFilePath,
        \Throwable $previous = null
    ): TranslationServiceAdapterException {
        return new TranslationServiceAdapterException(
            sprintf('Failed to close the translation archive "%s".', $actualFilePath),
            $previous
        );
    }
}
