<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\ORM\EntityManager;
use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;
use Oro\Bundle\UserBundle\Entity\User;

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

        /** @var User $user */
        $user = $em->getRepository('OroUserBundle:User')->createQueryBuilder('user')
            ->select('user')
            ->setMaxResults(1)
            ->orderBy('user.id')
            ->getQuery()
            ->getOneOrNullResult();

        // if we have no user in result - we are in install process, so we does not need to reindex search data
        if (!$user) {
            return;
        }

        $searchResult = $this->container->get('oro_search.index')->advancedSearch(
            sprintf(
                'from oro_user where username ~ %s and integer oro_user_owner = %d',
                $user->getUsername(),
                $user->getOwner()->getId()
            )
        );

        // if we have search result for username and it's owner - search data already contains data with owners.
        if ($searchResult->getRecordsCount()) {
            return;
        }

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
