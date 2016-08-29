<?php
namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Async;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Async\ExecuteProcessJobProcessor;

class ExecuteProcessJobProcessorTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeGetFromContainerAsService()
    {
        $processor = self::getContainer()->get('oro_workflow.async.execute_process_job');

        self::assertInstanceOf(ExecuteProcessJobProcessor::class, $processor);
    }
}
