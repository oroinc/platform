<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the activity owners to email activity list entities.
 */
class UpdateEmailActivityListOwners extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const BATCH_SIZE = 200;

    /**
     * @var ContainerInterface
     */
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
        $this->updateActivityListOwner($manager);
    }

    /**
     * Update ActivityList Owner
     *
     * @param ObjectManager $manager
     */
    public function updateActivityListOwner(ObjectManager $manager)
    {
        if ($this->container->hasParameter('installed') && $this->container->getParameter('installed')) {
            $qb = $manager->getRepository(ActivityList::class)
                ->createQueryBuilder('activityList');

            $qb->where($qb->expr()->eq('activityList.relatedActivityClass', ':class'))
                ->setParameter('class', Email::class);

            $iterator = new BufferedQueryResultIterator($qb);
            $iterator->setBufferSize(self::BATCH_SIZE);
            $itemsCount = 0;
            $entities = [];
            foreach ($iterator as $entity) {
                $entities[] = $entity;
                $itemsCount++;
                if (0 === $itemsCount % self::BATCH_SIZE) {
                    $this->addActivityListOwner($manager, $entities);
                    $entities = [];
                }
            }

            if ($itemsCount % static::BATCH_SIZE > 0) {
                $this->addActivityListOwner($manager, $entities);
            }
        }
    }

    /**
     * @param ObjectManager $manager
     * @param ActivityList[] $entities
     */
    public function addActivityListOwner(ObjectManager $manager, $entities)
    {
        foreach ($entities as $entity) {
            /** @var Email $email */
            $email = $manager->getRepository(Email::class)->find($entity->getRelatedActivityId());

            if ($email) {
                $activityOwners = [];
                foreach ($email->getEmailUsers() as $emailUser) {
                    if ($emailUser->getOwner() && in_array($emailUser->getOwner()->getId(), $activityOwners, true)) {
                        // skip users already assigned as owner
                        continue;
                    }

                    $activityOwner = $this->getActivityOwner($manager, $entity, $emailUser);
                    if (!$activityOwner) {
                        $activityOwner = $this->prepareActivityOwner($entity, $emailUser);
                        if ($emailUser->getOwner()) {
                            $activityOwners[] = $emailUser->getOwner()->getId();
                        }
                        $manager->persist($activityOwner);
                    }
                }
            }
        }

        $manager->flush();
        $manager->clear();
    }

    /**
     * @param ActivityList $activityList
     * @param EmailUser $emailUser
     * @return ActivityOwner
     */
    public function prepareActivityOwner(ActivityList $activityList, EmailUser $emailUser)
    {
        $activityOwner = new ActivityOwner();
        $activityOwner->setActivity($activityList)
            ->setOrganization($emailUser->getOrganization())
            ->setUser($emailUser->getOwner());

        return $activityOwner;
    }

    /**
     * @param ObjectManager $manager
     * @param ActivityList $activityList
     * @param EmailUser $emailUser
     * @return null|ActivityOwner
     */
    private function getActivityOwner(ObjectManager $manager, ActivityList $activityList, EmailUser $emailUser)
    {
        return $manager->getRepository(ActivityOwner::class)->findOneBy([
            'user' => $emailUser->getOwner(),
            'organization' => $emailUser->getOrganization(),
            'activity' => $activityList
        ]);
    }
}
