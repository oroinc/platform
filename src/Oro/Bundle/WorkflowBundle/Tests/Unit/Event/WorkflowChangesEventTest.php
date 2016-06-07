<?php
/**
 * Created by PhpStorm.
 * User: Matey
 * Date: 07.06.2016
 * Time: 15:58
 */

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;

class WorkflowChangesEventTest extends \PHPUnit_Framework_TestCase
{
    public function testDefinitionAware()
    {
        $definition = new WorkflowDefinition();

        $event = new WorkflowChangesEvent($definition);

        $this->assertSame($definition, $event->getDefinition());
    }
}
