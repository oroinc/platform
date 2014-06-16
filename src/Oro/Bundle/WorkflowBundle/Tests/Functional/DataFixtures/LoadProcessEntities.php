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

        $definition = new ProcessDefinition();
        $definition->setName('test')
            ->setLabel('Test');

        $entityManager->persist($definition);
        $entityManager->flush($definition);

        $existingTrigger = new ProcessTrigger();
        $existingTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setField('name');

        $entityManager->persist($existingTrigger);
        $entityManager->flush($existingTrigger);
    }
}
