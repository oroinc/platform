<?php

namespace Oro\Bundle\SearchBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;

class AddSearchReindexJob extends AbstractFixture implements ContainerAwareInterface
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
        $em  = $this->container->get('doctrine')->getManager();

        /** @var ObjectMapper $searchObjectMapper */
        $searchObjectMapper = $this->container->get('oro_search.mapper');

        $entityClasses = $searchObjectMapper->getEntities();
        foreach ($entityClasses as $entityClass) {
            $job = new Job(ReindexCommand::COMMAND_NAME, [$entityClass]);
            $em->persist($job);
        }

        $em->flush();
    }
}
