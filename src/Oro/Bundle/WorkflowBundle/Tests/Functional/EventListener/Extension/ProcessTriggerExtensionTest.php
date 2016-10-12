<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\EventListener\Extension;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\ProcessTriggerExtension;

class ProcessTriggerExtensionTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $listener = self::getContainer()->get('oro_workflow.listener.extension.process_trigger');

        self::assertInstanceOf(ProcessTriggerExtension::class, $listener);
    }
}
