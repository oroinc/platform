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
            $emailUser = $manager->getRepository('OroEmailBundle:EmailUser')->findOneBy(['email' => $result]);
            if ($itemsCount === 0) {
                $folder = $emailUser->getFolder();
                $email = $emailUser->getEmail();
                $owner = $email->getFromEmailAddress()->getOwner();
            }
            if ($owner instanceof User) {
                $owner = $manager->getRepository('OroUserBundle:User')->find($owner->getId());
                $emailUser->setFolder($folder);
                $emailUser->setEmail($email);
                $emailUser->setOwner($owner);
                $emailUser->setOrganization($owner->getOrganization());
                $itemsCount++;
                $entities[] = $emailUser;
            }

            foreach ($email->getRecipients() as $recipient) {
                $owner = $recipient->getEmailAddress()->getOwner();
                if ($owner instanceof User) {
                    $newEmailUser = new EmailUser();
                    $newEmailUser->setFolder($folder);
                    $newEmailUser->setEmail($email);
                    $newEmailUser->setReceivedAt($emailUser->getReceivedAt());
                    $newEmailUser->setSeen($emailUser->isSeen());
                    $newEmailUser->setOwner($owner);
                    $newEmailUser->setOrganization($owner->getOrganization());
                    $itemsCount++;
                    $entities[] = $newEmailUser;
                }
            }

            if (0 === $itemsCount % self::BATCH_SIZE) {
                $this->saveEntities($manager, $entities);
                $entities = [];
                $folder = $manager->getRepository('OroEmailBundle:EmailFolder')->find($emailUser->getFolder()->getId());
                $email = $manager->getRepository('OroEmailBundle:Email')->find($emailUser->getEmail()->getId());
                $owner = $email->getFromEmailAddress()->getOwner();
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
