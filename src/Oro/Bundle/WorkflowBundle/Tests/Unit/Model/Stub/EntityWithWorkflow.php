<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareTrait;

class EntityWithWorkflow implements WorkflowAwareInterface
{
    use WorkflowAwareTrait;
}
