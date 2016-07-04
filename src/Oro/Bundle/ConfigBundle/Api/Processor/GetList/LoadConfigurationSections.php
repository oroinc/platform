<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ConfigBundle\Api\Processor\GetScope;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Loads configuration sections.
 */
class LoadConfigurationSections implements ProcessorInterface
{
    /** @var ConfigurationRepository */
    protected $configRepository;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConfigurationRepository $configRepository
     * @param SecurityFacade          $securityFacade
     */
    public function __construct(ConfigurationRepository $configRepository, SecurityFacade $securityFacade)
    {
        $this->configRepository = $configRepository;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        // Here we need to check if an access to system configuration is granted,
        // because it is possible that some configuration sections will be accessible
        // event if "Access system configuration" capability is disabled.
        // So, such sections will be added by separate loaders in additional to existing sections.
        $isAccessToSystemConfigurationGranted = true;
        $aclResource = $context->getConfig()->getAclResource();
        if ($aclResource && !$this->securityFacade->isGranted($aclResource)) {
            $isAccessToSystemConfigurationGranted = false;
        }

        $sections = [];
        if ($isAccessToSystemConfigurationGranted) {
            $sectionIds = $this->configRepository->getSectionIds();
            foreach ($sectionIds as $sectionId) {
                $sections[] = $this->configRepository->getSection(
                    $sectionId,
                    $context->get(GetScope::CONTEXT_PARAM)
                );
            }
        }

        $context->setResult($sections);
    }
}
