<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Checker\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Feature toggle voter for Metadata Service functionality.
 * Controls whether Metadata Service-based metadata preservation is available in the system.
 *
 * This voter is automatically disabled when the Metadata Service is not configured,
 * preventing runtime errors if metadata preservation is enabled in configuration but the service is not available.
 */
class MetadataServiceVoter implements VoterInterface
{
    public const string ATTACHMENT_METADATA_SERVICE = 'attachment_metadata_service';

    private bool $isEnabled = false;

    public function setEnabled(bool $isEnabled): void
    {
        $this->isEnabled = $isEnabled;
    }

    #[\Override]
    public function vote($feature, $scopeIdentifier = null): int
    {
        if ($feature === self::ATTACHMENT_METADATA_SERVICE) {
            return $this->isEnabled ? self::FEATURE_ENABLED : self::FEATURE_DISABLED;
        }

        return self::FEATURE_ABSTAIN;
    }
}
