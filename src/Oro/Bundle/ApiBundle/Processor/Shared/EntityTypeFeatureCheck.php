<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an feature is enabled for the type of entities specified
 * in the "class" property of the Context.
 */
class EntityTypeFeatureCheck implements ProcessorInterface
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
     * @param Context $context
     *
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $entityClass = $context->getClassName();
        if (!$this->featureChecker->isResourceEnabled($entityClass, self::API_RESOURCE_KEY)) {
            throw new AccessDeniedException();
        }
    }
}
