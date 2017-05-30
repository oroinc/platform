<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Command\LoadWorkflowDefinitionsCommand;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;

abstract class WorkflowTestCase extends WebTestCase
{
    /**
     * @var \ReflectionProperty
     */
    private static $configDirPropertyReflection;

    /**
     * Loads workflow by command from workflow.yml file under specified directory.
     *
     * @param string $directory
     *
     * @return string
     */
    public static function loadWorkflowFrom($directory)
    {
        self::setConfigLoadDirectory($directory);
        return self::runCommand(LoadWorkflowDefinitionsCommand::NAME, [], true, true);
    }

    /**
     * @param string $directory
     */
    private static function setConfigLoadDirectory($directory)
    {
        self::getConfigDirPropertyReflection()->setValue(
            self::getContainer()->get('oro_workflow.configuration.provider.workflow_config'),
            $directory
        );
    }

    /**
     * @return \ReflectionProperty
     */
    private static function getConfigDirPropertyReflection()
    {
        $reflectionClass = new \ReflectionClass(WorkflowConfigurationProvider::class);

        if (!self::$configDirPropertyReflection) {
            self::$configDirPropertyReflection = $reflectionClass->getProperty('configDirectory');
            self::$configDirPropertyReflection->setAccessible(true);
        }

        return self::$configDirPropertyReflection;
    }

    /**
     * @return \Oro\Bundle\WorkflowBundle\Model\WorkflowManager
     */
    protected static function getSystemWorkflowManager()
    {
        return self::getContainer()->get('oro_workflow.registry.workflow_manager')->getManager('system');
    }

    /**
     * @return \Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry
     */
    protected static function getSystemWorkflowRegistry()
    {
        return self::getContainer()->get('oro_workflow.registry.system');
    }

    /**
     * @param string $class
     *
     * @return EntityManager
     */
    protected function getEntityManager($class)
    {
        $doctrineHelper = self::getContainer()->get('oro_entity.doctrine_helper');

        return $doctrineHelper->getEntityManagerForClass($class);
    }

    /**
     * @param $expected
     * @param null|string $workflowName
     */
    protected function assertWorkflowItemsCount($expected, $workflowName = null)
    {
        $criteria = ['entityClass' => WorkflowAwareEntity::class];

        if ($workflowName) {
            $criteria['workflowName'] = $workflowName;
        }
        $class = WorkflowItem::class;
        $this->assertCount($expected, $this->getEntityManager($class)->getRepository($class)->findBy($criteria));
    }

    /**
     * @param $expected
     */
    protected function assertWorkflowTransitionRecordCount($expected)
    {
        $class = WorkflowTransitionRecord::class;
        $this->assertCount($expected, $this->getEntityManager($class)->getRepository($class)->findAll());
    }

    /**
     * @param bool $flush
     *
     * @return WorkflowAwareEntity
     */
    protected function createWorkflowAwareEntity($flush = true)
    {
        $em = $this->getEntityManager(WorkflowAwareEntity::class);
        $entity = new WorkflowAwareEntity();
        $em->persist($entity);
        if ($flush) {
            $em->flush($entity);
        }

        return $entity;
    }
}
