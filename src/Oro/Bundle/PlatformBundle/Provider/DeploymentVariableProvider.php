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
                'oro.platform.environment.label',
                $this->parametersBag->has('kernel.environment') ? $this->parametersBag->get('kernel.environment') : null
            ),
        ];
    }
}
