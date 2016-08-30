<?php
namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\EventListener\ProcessCollectorListener;

class ProcessCollectorListenerTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $listener = self::getContainer()->get('oro_workflow.listener.process_collector');

        self::assertInstanceOf(ProcessCollectorListener::class, $listener);
    }
}
