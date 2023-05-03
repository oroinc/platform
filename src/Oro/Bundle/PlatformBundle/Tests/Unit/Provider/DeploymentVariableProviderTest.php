<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider;

use Oro\Bundle\PlatformBundle\Model\DeploymentVariable;
use Oro\Bundle\PlatformBundle\Provider\DeploymentVariableProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DeploymentVariableProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetVariables()
    {
        $parameters = new ParameterBag([
            'kernel.environment' => 'local',
        ]);
        $provider = new DeploymentVariableProvider($parameters);

        $this->assertEquals(
            [
                DeploymentVariable::create('oro.platform.environment.label', 'local'),
            ],
            $provider->getVariables()
        );
    }

    public function testGetVariablesWithEmptyValues()
    {
        $provider = new DeploymentVariableProvider(new ParameterBag());

        $this->assertEquals(
            [
                DeploymentVariable::create('oro.platform.environment.label'),
            ],
            $provider->getVariables()
        );
    }
}
