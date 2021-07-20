<?php

namespace Oro\Bundle\PlatformBundle\Provider;

use Oro\Bundle\PlatformBundle\Model\DeploymentVariable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Deployment vars provider
 */
class DeploymentVariableProvider
{
    /** @var ParameterBag */
    private $parametersBag;

    public function __construct(ParameterBag $parametersBag)
    {
        $this->parametersBag = $parametersBag;
    }

    /**
     * @return DeploymentVariable[]
     */
    public function getVariables()
    {
        return [
            DeploymentVariable::create(
                'oro.platform.deployment_type.label',
                $this->parametersBag->has('deployment_type') ? $this->parametersBag->get('deployment_type') : null
            ),
        ];
    }
}
