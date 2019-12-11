<?php

namespace Oro\Bundle\DistributionBundle\Resolver;

use Symfony\Component\Yaml\Yaml;

/**
 * Resolve deployment config
 */
class DeploymentConfigResolver
{
    /**
     * @param string $projectDir
     * @return string|null
     * @throws \LogicException
     */
    public static function resolveConfig(string $projectDir): ?string
    {
        $parametersConfig = $projectDir . '/config/parameters.yml';
        if (!file_exists($parametersConfig)) {
            return null;
        }

        $parameters = Yaml::parse(file_get_contents($parametersConfig)) ?: [];

        $deploymentType = $parameters['parameters']['deployment_type'] ?? '';
        if (!$deploymentType) {
            return null;
        }

        $deploymentConfig = sprintf('%s/config/deployment/config_%s.yml', $projectDir, $deploymentType);
        if (!file_exists($deploymentConfig)) {
            throw new \LogicException(
                sprintf('Deployment config "%s" for type "%s" not found.', $deploymentConfig, $deploymentType)
            );
        }

        return $deploymentConfig;
    }
}
