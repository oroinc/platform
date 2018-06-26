<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessLogger;

class ProcessLoggerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param bool $hasLogger
     * @param bool $hasCron
     * @dataProvider debugDataProvider
     */
    public function testDebug($hasLogger, $hasCron = false)
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $definitionName = 'test_definition';
        $definition = new ProcessDefinition();
        $definition->setName($definitionName);

        $triggerCron = '* * * * *';
        $triggerEvent = ProcessTrigger::EVENT_UPDATE;
        $trigger = new ProcessTrigger();
        $trigger->setDefinition($definition);
        if ($hasCron) {
            $trigger->setCron($triggerCron);
        } else {
            $trigger->setEvent($triggerEvent);
        }

        $entity = new \stdClass();
        $entityId = 1;
        if ($hasCron) {
            $data = new ProcessData();
        } else {
            $data = new ProcessData(array('data' => $entity));
        }

        $message = 'Test debug message';
        if ($hasCron) {
            $context = array('definition' => $definitionName, 'cron' => $triggerCron);
        } else {
            $context = array('definition' => $definitionName, 'event' => $triggerEvent, 'entityId' => $entityId);
        }

        if ($hasLogger) {
            if ($hasCron) {
                $doctrineHelper->expects($this->never())->method('getSingleEntityIdentifier');
            } else {
                $doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->with($entity, false)
                    ->will($this->returnValue($entityId));
            }
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $logger->expects($this->once())->method('debug')->with($message, $context);
        } else {
            $doctrineHelper->expects($this->never())->method('getSingleEntityIdentifier');
            $logger = null;
        }

        $processLogger = new ProcessLogger($doctrineHelper, $logger);
        $processLogger->debug($message, $trigger, $data);
    }

    /**
     * @return array
     */
    public function debugDataProvider()
    {
        return array(
            'with logger and cron'         => array('hasLogger' => true, 'hasCron' => true),
            'with logger and without cron' => array('hasLogger' => true),
            'without logger'               => array('hasLogger' => false),
        );
    }

    public function testNotEnabled()
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->createMock('Psr\Log\LoggerInterface');
        $logger->expects($this->never())->method('debug');
        $processLogger = new ProcessLogger($doctrineHelper, null);
        $processLogger->setEnabled(false);
        $trigger = new ProcessTrigger();
        $data = new ProcessData();
        $processLogger->debug('message', $trigger, $data);
    }
}
