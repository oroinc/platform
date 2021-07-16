<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Extension\FeatureConfigurationExtension;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Validates whether an feature is enabled for the type of entities specified
 * in the "class" property of the context.
 */
class ValidateEntityTypeFeature implements ProcessorInterface
{
    /** @var FeatureChecker */
    private $featureChecker;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if (!$this->featureChecker->isResourceEnabled(
            $context->getClassName(),
            FeatureConfigurationExtension::API_RESOURCE_KEY
        )) {
            throw new NotFoundHttpException();
        }
    }
}
