<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\UserBundle\Entity\User;

class UpdateOroEmailUser extends AbstractFixture implements DependentFixtureInterface
{
    const BATCH_SIZE = 100;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\EmailBundle\Migrations\Data\ORM\LoadInternalEmailOrigins',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $queryBuilder = $manager->getRepository('OroEmailBundle:Email')
            ->createQueryBuilder('e')
            ->select('e');
        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);
        $iterator->setHydrationMode(AbstractQuery::HYDRATE_OBJECT);

        $itemsCount = 0;
        $entities   = [];

        foreach ($iterator as $result) {
            /** @var EmailUser $emailUser */
            $emailUser = $manager->getRepository('OroEmailBundle:EmailUser')->findBy(['email' => $result]);
            /** @var Email $result */
            $owner = $result->getFromEmailAddress()->getOwner();
            if ($owner instanceof User) {
                $emailUser->setOwner($owner);
                $emailUser->setOrganization($owner->getOrganization());
                $entities[] = $emailUser;
            }

            foreach ($result->getRecipients() as $recipient) {
                $owner = $recipient->getEmailAddress()->getOwner();
                if ($owner instanceof User) {
                    $newEmailUser = new EmailUser();
                    $newEmailUser->setFolder($emailUser->getFolder());
                    $newEmailUser->setEmail($emailUser->getEmail());
                    $newEmailUser->setReceivedAt($emailUser->getReceivedAt());
                    $newEmailUser->setSeen($emailUser->isSeen());
                    $newEmailUser->setOwner($owner);
                    $newEmailUser->setOrganization($owner->getOrganization());
                    $entities[] = $newEmailUser;
                }
            }

            if (0 === $itemsCount % self::BATCH_SIZE) {
                $this->saveEntities($manager, $entities);
                $entities = [];
            }
        }

        if ($itemsCount % self::BATCH_SIZE > 0) {
            $this->saveEntities($manager, $entities);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array         $entities
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
