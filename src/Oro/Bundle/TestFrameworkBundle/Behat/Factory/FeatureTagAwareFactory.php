<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Factory;

use Oro\Bundle\TestFrameworkBundle\Behat\BehatFeature;

/**
 * Create service based on whether a specific Behat tag is active
 */
class FeatureTagAwareFactory
{
    public function __construct(
        private BehatFeature $behatFeature
    ) {
    }

    public function __invoke(
        object $defaultService,
        array ...$servicesByBehatFeatureTags
    ): object {
        foreach ($servicesByBehatFeatureTags as [$alternativeService, $tag]) {
            if ($this->behatFeature->hasTag($tag)) {
                return $alternativeService;
            }
        }

        return $defaultService;
    }
}
