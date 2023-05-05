<?php

namespace Oro\Bundle\AttachmentBundle\Checker\Voter;

use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Checks whether libraries are present in the system.
 */
class PostProcessorsVoter implements VoterInterface
{
    public const ATTACHMENT_POST_PROCESSORS = 'attachment_post_processors';

    private ProcessorHelper $processorHelper;

    public function __construct(ProcessorHelper $processorHelper)
    {
        $this->processorHelper = $processorHelper;
    }

    /**
     * @inhericDoc
     */
    public function vote($feature, $scopeIdentifier = null): int
    {
        if ($feature === self::ATTACHMENT_POST_PROCESSORS) {
            try {
                $librariesExists = $this->processorHelper->librariesExists();
            } catch (\Exception $exception) {
                return self::FEATURE_DISABLED;
            }

            return $librariesExists ? self::FEATURE_ENABLED : self::FEATURE_DISABLED;
        }

        return self::FEATURE_ABSTAIN;
    }
}
