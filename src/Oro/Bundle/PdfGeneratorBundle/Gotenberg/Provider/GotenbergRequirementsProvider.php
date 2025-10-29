<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Gotenberg\Provider;

use Oro\Bundle\InstallerBundle\Provider\AbstractRequirementsProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Requirements\RequirementCollection;

/**
 * Requirements provider for Gotenberg API
 */
class GotenbergRequirementsProvider extends AbstractRequirementsProvider
{
    public const REQUIRED_VERSION = '8.5.0';
    private ?string $serverVersion = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $gotenbergApiUrl,
    ) {
    }

    #[\Override]
    public function getRecommendations(): RequirementCollection
    {
        $collection = new RequirementCollection();

        $this->addGotenbergUrlRecommendation($collection);
        $this->addApiAccessibleRecommendation($collection);
        $this->addVersionRequirement($collection);

        return $collection;
    }

    private function addGotenbergUrlRecommendation(RequirementCollection $collection): void
    {
        $isConfigured = !empty($this->gotenbergApiUrl);

        $collection->addRecommendation(
            $isConfigured,
            'Gotenberg API URL is configured',
            'Please set the "ORO_PDF_GENERATOR_GOTENBERG_API_URL" environment variable ' .
            'to enable PDF generation.'
        );
    }

    private function addApiAccessibleRecommendation(RequirementCollection $collection): void
    {
        if ($this->gotenbergApiUrl === null) {
            return;
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('%s/version', $this->gotenbergApiUrl)
            );
            $statusCode = $response->getStatusCode();

            if ($statusCode === Response::HTTP_OK) {
                $content = $response->getContent();
                if (preg_match('/^([^\s-]+)/', $content, $matches)) {
                    $this->serverVersion = $matches[1];
                }
            }

            $collection->addRequirement(
                $statusCode === Response::HTTP_OK,
                'Gotenberg API Is Accessible',
                $statusCode === Response::HTTP_NOT_FOUND
                    ? sprintf(
                        'Gotenberg version is below %s (endpoint /version not found). Please upgrade Gotenberg.',
                        self::REQUIRED_VERSION
                    )
                    : sprintf(
                        'Gotenberg API HTTP Status: %s. Version %s or higher is required.',
                        $statusCode,
                        self::REQUIRED_VERSION
                    )
            );
        } catch (TransportExceptionInterface $exception) {
            $collection->addRequirement(
                false,
                'Gotenberg API Is Accessible',
                sprintf('Failed to connect to Gotenberg API. Error: %s', $exception->getMessage())
            );
        }
    }

    private function addVersionRequirement(RequirementCollection $collection): void
    {
        if ($this->gotenbergApiUrl !== null && $this->serverVersion !== null) {
            $collection->addRequirement(
                version_compare($this->serverVersion, self::REQUIRED_VERSION, '>='),
                'Connected to required Gotenberg version (' . $this->serverVersion . ')',
                'Gotenberg version must be ' . self::REQUIRED_VERSION . ' or higher'
            );
        }
    }
}
