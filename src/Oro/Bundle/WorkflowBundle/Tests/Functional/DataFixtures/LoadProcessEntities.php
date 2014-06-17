<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadProcessEntities extends AbstractFixture implements ContainerAwareInterface
{
    const FIRST_DEFINITION = 'first';
    const SECOND_DEFINITION = 'second';
    const UPDATE_TRIGGER_FIELD = 'name';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $entityManager = $this->container->get('doctrine')->getManager();

        // first definition
        $firstDefinition = new ProcessDefinition();
        $firstDefinition->setName(self::FIRST_DEFINITION)
            ->setLabel(self::FIRST_DEFINITION)
            ->setExecutionOrder(10);

        $updateTrigger = new ProcessTrigger();
        $updateTrigger->setDefinition($firstDefinition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setField(self::UPDATE_TRIGGER_FIELD);

        $entityManager->persist($firstDefinition);
        $entityManager->persist($updateTrigger);

        // second definition
        $secondDefinition  = new ProcessDefinition();
        $secondDefinition->setName(self::SECOND_DEFINITION)
            ->setLabel(self::SECOND_DEFINITION)
            ->setExecutionOrder(20);

        $createTrigger = new ProcessTrigger();
        $createTrigger->setDefinition($secondDefinition)
            ->setEvent(ProcessTrigger::EVENT_CREATE);

        $deleteTrigger = new ProcessTrigger();
        $deleteTrigger->setDefinition($secondDefinition)
            ->setEvent(ProcessTrigger::EVENT_DELETE);

        $entityManager->persist($secondDefinition);
        $entityManager->persist($createTrigger);
        $entityManager->persist($deleteTrigger);

        $entityManager->flush();
    }
}
