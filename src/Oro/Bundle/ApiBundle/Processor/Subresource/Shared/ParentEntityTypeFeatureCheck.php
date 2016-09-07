<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an feature is enabled for the type of entities specified
 * in the "parentClass" property of the Context.
 */
class ParentEntityTypeFeatureCheck implements ProcessorInterface
{
    const API_RESOURCE_KEY = 'api_resources';

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
     * @var SubresourceContext $context
     *
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $parentClassName = $context->getParentClassName();
        if (!$this->featureChecker->isResourceEnabled($parentClassName, self::API_RESOURCE_KEY)) {
            throw new AccessDeniedException();
        }
    }
}
