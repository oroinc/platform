<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Model;

use Oro\Bundle\PlatformBundle\Model\DeploymentVariable;

class DeploymentVariableTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $var = DeploymentVariable::create('label', 'value');

        $this->assertEquals('label', $var->getLabel());
        $this->assertEquals('value', $var->getValue());
    }
    public function testCreateWitoutValue()
    {
        $var = DeploymentVariable::create('label');

        $this->assertEquals('label', $var->getLabel());
        $this->assertNull($var->getValue());
    }
}
