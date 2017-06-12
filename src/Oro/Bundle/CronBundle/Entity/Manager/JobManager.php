<?php

namespace Oro\Bundle\CronBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\ClassUtils;

use JMS\JobQueueBundle\Entity\Job;
use Symfony\Component\PropertyAccess\PropertyAccess;

class JobManager
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns basic query instance to get collection with all job instances
     *
     * @return QueryBuilder
     */
    public function getListQuery()
    {
        $qb = $this->em->createQueryBuilder();

        return $qb
            ->select('j')
            ->from('JMSJobQueueBundle:Job', 'j')
            ->where($qb->expr()->isNull('j.originalJob'))
            ->orderBy('j.createdAt', 'DESC');
    }

    public function getRelatedEntities(Job $job)
    {
        $related = array();

        foreach ($job->getRelatedEntities() as $entity) {
            $class = ClassUtils::getClass($entity);

            $related[] = array(
                'class' => $class,
                'id'    => json_encode($this->em->getClassMetadata($class)->getIdentifierValues($entity)),
                'raw'   => $entity,
            );
        }

        return $related;
    }

    public function getJobStatistics(Job $job)
    {
        $statisticData         = array();
        $dataPerCharacteristic = array();

        $stmt = $this->em->getConnection()->prepare('SELECT * FROM jms_job_statistics WHERE job_id = :jobId');
        $stmt->execute(array('jobId' => $job->getId()));
        $statistics = $stmt->fetchAll();

        $propertyAccess = PropertyAccess::createPropertyAccessor();
        foreach ($statistics as $row) {
            $dataPerCharacteristic[$propertyAccess->getValue($row, '[characteristic]')][] = array(
                $propertyAccess->getValue($row, '[createdAt]'),
                $propertyAccess->getValue($row, '[charValue]')
            );
        }

        if ($dataPerCharacteristic) {
            $statisticData = array(array_merge(array('Time'), $chars = array_keys($dataPerCharacteristic)));
            $startTime     = strtotime($dataPerCharacteristic[$chars[0]][0][0]);
            $endTime       = strtotime(
                $dataPerCharacteristic[$chars[0]][count($dataPerCharacteristic[$chars[0]])-1][0]
            );
            $scaleFactor   = $endTime - $startTime > 300 ? 1/60 : 1;

            // This assumes that we have the same number of rows for each characteristic.
            for ($i = 0, $c = count(reset($dataPerCharacteristic)); $i < $c; $i++) {
                $row = array((strtotime($dataPerCharacteristic[$chars[0]][$i][0]) - $startTime) * $scaleFactor);

                foreach ($chars as $name) {
                    $value = (float) $dataPerCharacteristic[$name][$i][1];

                    switch ($name) {
                        case 'memory':
                            $value /= 1024 * 1024;
                            break;
                    }

                    $row[] = $value;
                }

                $statisticData[] = $row;
            }
        }

        return $statisticData;
    }

    /**
     * Return count of current running jobs by given name
     *
     * @param string $commandName
     * @return int
     */
    public function getRunningJobsCount($commandName)
    {
        return (int) $this->em
            ->getRepository('JMSJobQueueBundle:Job')
            ->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->andWhere('j.command=:commandName')
            ->andWhere('j.state=:stateName')
            ->setParameter('commandName', $commandName)
            ->setParameter('stateName', Job::STATE_RUNNING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string      $name
     * @param null|string $args
     *
     * @return mixed
     */
    public function getJobsInQueueCount($name, $args = null)
    {
        $qb = $this->em->getRepository('JMSJobQueueBundle:Job')
            ->createQueryBuilder('j');

        $qb->select('count(j.id)')
            ->andWhere('j.command=:command')
            ->andWhere('j.state in (:stateName)')
            ->setParameter('command', $name)
            ->setParameter('stateName', [Job::STATE_NEW, Job::STATE_RUNNING, Job::STATE_PENDING]);

        if ($args) {
            $qb->andWhere($qb->expr()->like('cast(j.args as text)', ':args'));
            $qb->setParameter('args', $args);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string      $name
     * @param null|string $args
     *
     * @return bool
     */
    public function hasJobInQueue($name, $args = null)
    {
        return $this->getJobsInQueueCount($name, $args) > 0;
    }
}
