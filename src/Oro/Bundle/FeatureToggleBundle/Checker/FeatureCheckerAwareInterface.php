<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

interface FeatureCheckerAwareInterface
{
    /**
     * @param FeatureChecker $featureChecker
     * @return mixed
     */
    public function setFeatureChecker(FeatureChecker $featureChecker);
}
