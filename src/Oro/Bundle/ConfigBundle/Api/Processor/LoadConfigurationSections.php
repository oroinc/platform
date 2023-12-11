<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Loads configuration sections.
 */
class LoadConfigurationSections implements ProcessorInterface
{
    /** @var ConfigurationRepository */
    protected $configRepository;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

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
    public function process(ContextInterface $context)
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $config = $context->getConfig();

        $sections = [];
        // Here we need to check if an access to system configuration is granted,
        // because it is possible that some configuration sections can be accessible
        // even if an access is denied by ACL.
        // So, the "security_check" group can be skipped for such sections
        // and them can be added by other API processors, in additional to existing sections.
        if ($this->isSystemConfigurationAccessGranted($config->getAclResource())) {
            $scope = $context->get(GetScope::CONTEXT_PARAM);
            $withOptions = !$config->getField('options')->isExcluded();
            $sectionIds = $this->configRepository->getSectionIds();
            foreach ($sectionIds as $sectionId) {
                $sections[] = $this->configRepository->getSection($sectionId, $scope, $withOptions);
            }
        }

        $context->setResult($sections);
    }

    private function isSystemConfigurationAccessGranted(?string $aclResource): bool
    {
        return !$aclResource || $this->authorizationChecker->isGranted($aclResource);
    }
}
