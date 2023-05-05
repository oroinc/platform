<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether the search capability is enabled.
 */
class ValidateSearchCapability implements ProcessorInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$this->authorizationChecker->isGranted('oro_search')) {
            throw new AccessDeniedException('The search capability is disabled.');
        }
    }
}
