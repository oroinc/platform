<?php

namespace Oro\Bundle\FeatureToggleBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class ApiExclusionProvider extends AbstractExclusionProvider
{
    const API_RESOURCE_KEY = 'api';

    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        return !$this->featureChecker->isResourceEnabled($className, self::API_RESOURCE_KEY);
    }
}
