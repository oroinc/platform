<?php

namespace Oro\Bundle\SearchBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\ORM\EntityManager;
use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;

class AddSearchReindexJob extends AbstractFixture implements ContainerAwareInterface
{
    const INDEXATION_LIMIT = 10000;

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
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        /** @var ObjectMapper $searchObjectMapper */
        $searchObjectMapper = $this->container->get('oro_search.mapper');

        $entityClasses = $searchObjectMapper->getEntities();
        foreach ($entityClasses as $entityClass) {
            $countQuery = $em->createQueryBuilder()
                ->select('count(entity)')
                ->from($entityClass, 'entity');

            $count = $countQuery->getQuery()->getSingleScalarResult();
            if ($count > self::INDEXATION_LIMIT) {
                $jobsCount = ceil($count / self::INDEXATION_LIMIT);
                for ($i = 0; $i < $jobsCount; $i++) {
                    $offset = $i * self::INDEXATION_LIMIT;
                    $job    = new Job(
                        ReindexCommand::COMMAND_NAME,
                        [
                            $entityClass,
                            $offset,
                            self::INDEXATION_LIMIT,
                            '-v'
                        ],
                        true,
                        JOB::DEFAULT_QUEUE,
                        $i == 0 ? JOB::PRIORITY_HIGH : JOB::PRIORITY_LOW
                    );
                    $em->persist($job);
                }
            } else {
                $job = new Job(
                    ReindexCommand::COMMAND_NAME,
                    [$entityClass, '-v'],
                    true,
                    JOB::DEFAULT_QUEUE,
                    JOB::PRIORITY_HIGH
                );
                $em->persist($job);
            }
        }

        $em->flush();
    }
}
