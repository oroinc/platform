<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Model;

use Oro\Bundle\PlatformBundle\Model\DeploymentVariable;
use PHPUnit\Framework\TestCase;

class DeploymentVariableTest extends TestCase
{
    public function testCreate(): void
    {
        $var = DeploymentVariable::create('label', 'value');

        $this->assertEquals('label', $var->getLabel());
        $this->assertEquals('value', $var->getValue());
    }
    public function testCreateWitoutValue(): void
    {
        $var = DeploymentVariable::create('label');

        $this->assertEquals('label', $var->getLabel());
        $this->assertNull($var->getValue());
    }
}
