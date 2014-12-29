<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessLogger;

class ProcessLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param bool $hasLogger
     * @dataProvider debugDataProvider
     */
    public function testDebug($hasLogger)
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $definitionName = 'test_definition';
        $definition = new ProcessDefinition();
        $definition->setName($definitionName);

        $triggerEvent = ProcessTrigger::EVENT_UPDATE;
        $trigger = new ProcessTrigger();
        $trigger->setEvent($triggerEvent)->setDefinition($definition);

        $entity = new \stdClass();
        $entityId = 1;
        $data = new ProcessData(array('data' => $entity));

        $message = 'Test debug message';
        $context = array('definition' => $definitionName, 'event' => $triggerEvent, 'entityId' => $entityId);

        if ($hasLogger) {
            $doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->with($entity, false)
                ->will($this->returnValue($entityId));
            $logger = $this->getMock('Psr\Log\LoggerInterface');
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
            'with logger'    => array('hasLogger' => true),
            'without logger' => array('hasLogger' => false),
        );
    }
}
