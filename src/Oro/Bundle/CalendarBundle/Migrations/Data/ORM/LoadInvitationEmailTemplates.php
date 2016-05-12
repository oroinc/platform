<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadInvitationEmailTemplates extends AbstractEmailFixture implements VersionedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->removeExistingTemplates($manager);
        parent::load($manager);
    }

    /**
     * Return path to email templates
     *
     * @return string
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroCalendarBundle/Migrations/Data/ORM/data/emails/invitation');
    }

    /**
     * @param EntityManager $manager
     */
    protected function removeExistingTemplates(EntityManager $manager)
    {
        $qb = $manager->getRepository('OroEmailBundle:EmailTemplate')->createQueryBuilder('t');
        $qb
            ->delete()
            ->where($qb->expr()->in('t.name', ':names'))
            ->setParameter(
                'names',
                [
                    'calendar_invitation_accepted',
                    'calendar_invitation_declined',
                    'calendar_invitation_delete_child_event',
                    'calendar_invitation_delete_parent_event',
                    'calendar_invitation_invite',
                    'calendar_invitation_tentative',
                    'calendar_invitation_uninvite',
                    'calendar_invitation_update',
                ]
            )
            ->getQuery()
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.1';
    }
}
