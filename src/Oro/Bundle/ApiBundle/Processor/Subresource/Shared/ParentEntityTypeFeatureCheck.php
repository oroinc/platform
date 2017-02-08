<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\FeatureConfigurationExtension;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Validates whether an feature is enabled for the type of entities specified
 * in the "parentClass" property of the Context.
 */
class ParentEntityTypeFeatureCheck implements ProcessorInterface
{
    /** @var FeatureChecker */
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
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        if (!$this->featureChecker->isResourceEnabled(
            $context->getParentClassName(),
            FeatureConfigurationExtension::API_RESOURCE_KEY
        )) {
            throw new AccessDeniedException();
        }
    }
}
