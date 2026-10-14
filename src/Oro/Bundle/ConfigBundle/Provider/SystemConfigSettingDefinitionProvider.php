<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides the definitions of the system configuration settings.
 */
class SystemConfigSettingDefinitionProvider
{
    public function __construct(
        private readonly ConfigBag $configBag,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly FeatureChecker $featureChecker
    ) {
    }

    /**
     * Returns the definition of the given system configuration setting from the {@see ConfigBag}.
     *
     * @param string $configKey The key of the system configuration setting, e.g. "oro_user.some_setting"
     *
     * @return array<string, mixed>
     *
     * @throws \LogicException When the setting does not exist
     */
    public function getDefinition(string $configKey): array
    {
        $definition = $this->configBag->getFieldsRoot($configKey);
        if (false === $definition) {
            throw new \LogicException(sprintf(
                'The system configuration setting "%s" does not exist.',
                $configKey
            ));
        }

        return $definition;
    }

    /**
     * Checks whether the given system configuration setting is available to the current user.
     *
     * @param string $configKey The key of the system configuration setting, e.g. "oro_user.some_setting"
     *
     * @throws \LogicException When the setting does not exist
     */
    public function isAvailable(string $configKey): bool
    {
        $definition = $this->getDefinition($configKey);

        return $this->featureChecker->isResourceEnabled($configKey, 'configuration')
            && (empty($definition['acl_resource'])
                || $this->authorizationChecker->isGranted($definition['acl_resource']));
    }
}
