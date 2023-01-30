<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * The class that helps to process "{@feature:...}" placeholders in a text.
 */
class FeatureDependedTextProcessor extends AbstractTextProcessor
{
    private const START_FEATURE_TAG = '{@feature:';
    private const END_FEATURE_TAG = '{@/feature}';

    private FeatureChecker $featureChecker;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * Checks whether the given text contains "{@feature:...}" placeholders and, if so, do the following:
     * * replaces placeholders related to the specific feature with their content
     * * removes placeholders that are not related to the specific feature
     */
    public function process(string $text): string
    {
        return $this->processText(
            $text,
            self::START_FEATURE_TAG,
            self::END_FEATURE_TAG,
            function ($value) {
                return $this->featureChecker->isFeatureEnabled($value);
            }
        );
    }
}
