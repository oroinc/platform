<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider;

use Oro\Bundle\PlatformBundle\Model\DeploymentVariable;
use Oro\Bundle\PlatformBundle\Provider\DeploymentVariableProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class DeploymentVariableProviderTest extends TestCase
{
    public function testGetVariables(): void
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

    public function testGetVariablesWithEmptyValues(): void
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
