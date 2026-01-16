<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\InstallerBundle\Provider\AbstractRequirementsProvider;
use Symfony\Requirements\RequirementCollection;

/**
 * Requirements provider for Metadata Service API
 */
class MetadataServiceRequirementsProvider extends AbstractRequirementsProvider
{
    public function __construct(
        private bool $isMetadataServiceEnabled,
        private MetadataServiceProvider $metadataServiceProvider,
    ) {
    }

    #[\Override]
    public function getRecommendations(): RequirementCollection
    {
        $collection = new RequirementCollection();

        $collection->addRecommendation(
            $this->isMetadataServiceEnabled,
            'Metadata Service URL and API Key are configured',
            'Please set the "ORO_METADATA_SERVICE_URL" and ' .
            '"ORO_METADATA_SERVICE_API_KEY" environment variables ' .
            'to enable image metadata preservation.'
        );

        if ($this->isMetadataServiceEnabled) {
            $collection->addRequirement(
                $this->metadataServiceProvider->isServiceHealthy(),
                'Metadata Service is accessible',
                'The Metadata Service is not accessible. Please check the service URL and API key.'
            );
        }

        return $collection;
    }
}
