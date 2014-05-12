<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Stubs;

use Doctrine\Common\Proxy\Proxy;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

abstract class StepProxyStub extends WorkflowStep implements Proxy
{
}
