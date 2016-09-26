<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Command;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessCronTriggerCommand;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;

/**
 * @dbIsolation
 */
class HandleProcessCronTriggerCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadWorkflowDefinitions::class
            ]
        );
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $expectedMessages
     */
    public function testExecute(array $expectedMessages)
    {
        $triggers = $this->getRepository('TransitionCronTrigger.php')->findAll();

        $result = $this->runCommand(HandleProcessCronTriggerCommand::NAME, ['--id' => '1']);

        $this->assertNotEmpty($result);
        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'expectedMessages' => [
                    ' ',
                ]
            ]
        ];
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className)->getRepository($className);
    }
}
