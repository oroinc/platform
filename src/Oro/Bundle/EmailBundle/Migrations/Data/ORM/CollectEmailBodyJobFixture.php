<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Command\MigrateEmailBodyCommand;

/**
 * Adds job to collect email body representations.
 * Will be deleted in 2.0
 */
class CollectEmailBodyJobFixture extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $job = new Job(MigrateEmailBodyCommand::COMMAND_NAME, []);
        $em  = $this->container->get('doctrine')->getManager();
        $em->persist($job);
        $em->flush($job);
    }
}
