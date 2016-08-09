<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

interface WorkflowApplicabilityFilterInterface
{
    /**
     * @param ArrayCollection|Workflow[] $workflows named collection of Workflow instances
     * @param WorkflowRecordContext $context
     *
     * @return ArrayCollection
     */
    public function filter(ArrayCollection $workflows, WorkflowRecordContext $context);
}
