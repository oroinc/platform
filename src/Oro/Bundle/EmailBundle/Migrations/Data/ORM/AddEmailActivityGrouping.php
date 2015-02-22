<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;

class AddEmailActivityGrouping extends AbstractFixture implements DependentFixtureInterface
{
    const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\EmailBundle\Migrations\Data\ORM\AddEmailActivityLists',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
//        $query = <<<SQL
//UPDATE oro_email
//   SET thread_id = x_thread_id
// WHERE thread_id IS NULL
//   AND x_thread_id IS NOT NULL
//SQL;
//
//        /** @var Connection $connection */
//        $connection = $manager->getConnection();
//        $connection->executeUpdate($query);

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->neq('xThreadId', null));
        /** @var QueryBuilder $threadQueryBuilder */
        $threadQueryBuilder = $manager->getRepository('OroEmailBundle:Email')->createQueryBuilder('entity');
        $threadQueryBuilder->distinct()->select('entity.xThreadId');
        $threadQueryBuilder->addCriteria($criteria);

        $iterator = new BufferedQueryResultIterator($threadQueryBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);

        $itemsCount = 0;
        $entities   = [];

        foreach ($iterator as $threadResult) {
            $threadId = $threadResult['xThreadId'];
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $manager->getRepository('OroEmailBundle:Email')->createQueryBuilder('entity');
            $criteria = new Criteria();
            $criteria->where($criteria->expr()->eq('xThreadId', $threadId));
            $criteria->orderBy(['created' => 'ASC']);
            $queryBuilder->addCriteria($criteria);
            $queryBuilder->setFirstResult(0);
            $emails = $queryBuilder->getQuery()->execute();
            if (count($emails)) {
                foreach ($emails as $key => $email) {
                    /** @var Email $email */
                    $newThread = new EmailThread();
//                    $newThread->setSubject($email->getSubject());
//                    $newThread->setSentAt($email->getSentAt());
                    $email->setThread($newThread);
                    if ($key == 0) {
                        $email->setHead(true);
                    } else {
                        $email->setHead(false);
                    }
                    $itemsCount++;
                    $entities[] = $email;
                    if (0 == $itemsCount % self::BATCH_SIZE) {
                        $this->saveEntities($manager, $entities);
                        $entities = [];
                    }
                }
            }
        }

        if ($itemsCount % self::BATCH_SIZE > 0) {
            $this->saveEntities($manager, $entities);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array $entities
     */
    protected function saveEntities(ObjectManager $manager, array $entities)
    {
        foreach ($entities as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();
        $manager->clear();
    }
}
