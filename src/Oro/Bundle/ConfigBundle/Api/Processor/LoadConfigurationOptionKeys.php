<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationOptionFilterVisitor;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Loads keys of configuration options.
 */
class LoadConfigurationOptionKeys implements ProcessorInterface
{
    public const OPTION_KEYS = '_configuration_option_keys';

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
        /** @var ListContext $context */

        if ($context->has(self::OPTION_KEYS)) {
            // option keys already retrieved
            return;
        }

        $optionKeys = $this->getRequestedOptionKeys($context->getCriteria());

        // Here we need to check if an access to system configuration is granted,
        // because it is possible that some configuration options can be accessible
        // even if an access is denied by ACL.
        // So, the "security_check" group can be skipped for such options
        // and them can be added by other API processors, in additional to existing options.
        $isSystemConfigurationAccessGranted = $this->isSystemConfigurationAccessGranted(
            $context->getConfig()->getAclResource()
        );
        if (null === $optionKeys) {
            $optionKeys = $isSystemConfigurationAccessGranted
                ? $this->configRepository->getOptionKeys()
                : [];
        } elseif (!$isSystemConfigurationAccessGranted) {
            $notSystemConfigurationOptionKeys = [];
            $systemConfigurationOptionKeyMap = array_fill_keys($this->configRepository->getOptionKeys(), true);
            foreach ($optionKeys as $optionKey) {
                if (!isset($systemConfigurationOptionKeyMap)) {
                    $notSystemConfigurationOptionKeys[] = $optionKey;
                }
            }
            $optionKeys = $notSystemConfigurationOptionKeys;
        }

        $context->set(self::OPTION_KEYS, $optionKeys);
    }

    private function getRequestedOptionKeys(?Criteria $criteria): ?array
    {
        if (null === $criteria) {
            return null;
        }
        $expr = $criteria->getWhereExpression();
        if (null === $expr) {
            return null;
        }

        $visitor = new ConfigurationOptionFilterVisitor();
        $visitor->dispatch($expr);
        $filters = $visitor->getFilters();
        $keys = $filters['key'] ?? null;
        if (null === $keys) {
            return null;
        }

        return (array)$keys;
    }

    private function isSystemConfigurationAccessGranted(?string $aclResource): bool
    {
        return !$aclResource || $this->authorizationChecker->isGranted($aclResource);
    }
}
