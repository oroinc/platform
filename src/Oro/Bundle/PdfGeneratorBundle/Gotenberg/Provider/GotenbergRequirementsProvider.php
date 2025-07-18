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

            $collection->addRecommendation(
                $statusCode === Response::HTTP_OK,
                'Gotenberg API Is Accessible',
                sprintf('Gotenberg API HTTP Status: %s', $statusCode)
            );
        } catch (TransportExceptionInterface $exception) {
            $collection->addRecommendation(
                false,
                'Gotenberg API Is Accessible',
                sprintf('Failed to connect to Gotenberg API. Error: %s', $exception->getMessage())
            );
        }
    }
}
