<?php

namespace Oro\Bundle\DistributionBundle\Resolver;

use Symfony\Component\Yaml\Yaml;

/**
 * Resolve deployment config path
 */
class DeploymentConfigResolver
{
    /**
     * @throws \LogicException
     */
    public static function resolveConfig(string $projectDir): ?string
    {
        $parametersConfig = $projectDir . '/config/parameters.yml';
        if (!file_exists($parametersConfig)) {
            return null;
        }

        $parameters = Yaml::parse(
            file_get_contents($parametersConfig),
            Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS
        ) ?: [];

        $deploymentType = $parameters['parameters']['deployment_type'] ?? '';
        if (!$deploymentType) {
            return null;
        }

        $deploymentConfig = sprintf('%s/config/deployment/config_%s.yml', $projectDir, $deploymentType);
        if (!file_exists($deploymentConfig)) {
            throw new \LogicException(
                sprintf(
                    'Deployment config "./config/deployment/config_%s.yml" for deployment type "%s" not found.',
                    $deploymentType,
                    $deploymentType
                )
            );
        }

        return $deploymentConfig;
    }
}
