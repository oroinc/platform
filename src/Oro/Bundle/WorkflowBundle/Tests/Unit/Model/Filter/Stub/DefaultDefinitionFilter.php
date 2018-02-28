<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Filter\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Model\Filter\WorkflowDefinitionFilterInterface;

class DefaultDefinitionFilter implements WorkflowDefinitionFilterInterface
{
    /**
     * {@iheritdoc}
     */
    public function filter(Collection $workflowDefinitions)
    {
        return $workflowDefinitions;
    }
}
