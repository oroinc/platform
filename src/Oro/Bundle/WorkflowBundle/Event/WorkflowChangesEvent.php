<?php
/**
 * Created by PhpStorm.
 * User: Matey
 * Date: 02.06.2016
 * Time: 16:49
 */

namespace Oro\Bundle\WorkflowBundle\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\EventDispatcher\Event;

class WorkflowChangesEvent extends Event
{
    /** @var WorkflowDefinition */
    private $definition;

    public function __construct(WorkflowDefinition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return WorkflowDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }
}