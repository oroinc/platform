<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Loads a configuration section.
 */
class LoadConfigurationSection implements ProcessorInterface
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

        $config = $context->getConfig();

        // Here we need to check if an access to system configuration is granted,
        // because it is possible that some configuration sections can be accessible
        // even if an access is denied by ACL.
        // So, the "security_check" group can be skipped for such sections
        // and them can be added by other API processors.
        if (!$this->isSystemConfigurationAccessGranted($config->getAclResource())) {
            return;
        }

        $section = $this->configRepository->getSection(
            $context->getId(),
            $context->get(GetScope::CONTEXT_PARAM),
            !$config->getField('options')->isExcluded()
        );
        if (null !== $section) {
            $context->setResult($section);
        }
    }

    private function isSystemConfigurationAccessGranted(?string $aclResource): bool
    {
        return !$aclResource || $this->authorizationChecker->isGranted($aclResource);
    }
}
