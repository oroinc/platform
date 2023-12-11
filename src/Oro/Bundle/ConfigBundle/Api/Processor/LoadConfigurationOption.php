<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Loads a configuration option.
 */
class LoadConfigurationOption implements ProcessorInterface
{
    private ConfigurationRepository $configRepository;
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(
        ConfigurationRepository $configRepository,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->configRepository = $configRepository;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        // Here we need to check if an access to system configuration is granted,
        // because it is possible that some configuration options can be accessible
        // even if an access is denied by ACL.
        // So, the "security_check" group can be skipped for such options
        // and them can be added by other API processors.
        if (!$this->isSystemConfigurationAccessGranted($context->getConfig()->getAclResource())) {
            return;
        }

        $option = $this->configRepository->getOption(
            $context->getId(),
            $context->get(GetScope::CONTEXT_PARAM)
        );
        if (null !== $option) {
            $context->setResult($option);
        }
    }

    private function isSystemConfigurationAccessGranted(?string $aclResource): bool
    {
        return !$aclResource || $this->authorizationChecker->isGranted($aclResource);
    }
}
