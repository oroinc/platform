<?php

namespace Oro\Bundle\EmailBundle\Model\WebSocket;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\UserBundle\Entity\User;

class WebSocketSendProcessor
{
    const TOPIC = 'oro/email_event/user_%s_org_%s';

    /**
     * @var TopicPublisher
     */
    protected $publisher;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @param TopicPublisher $publisher
     * @param Registry $doctrine
     */
    public function __construct(TopicPublisher $publisher, Registry $doctrine)
    {
        $this->publisher = $publisher;
        $this->doctrine = $doctrine;
    }

    /**
     * Get user topic
     *
     * @param User $user
     * @param Organization $organization
     * @return string
     */
    public static function getUserTopic(User $user, Organization $organization)
    {
        return sprintf(self::TOPIC, $user->getId(), $organization->getId());
    }

    /**
     * Send message into topic
     *
     * @param array $usersWithNewEmails
     */
    public function send($usersWithNewEmails)
    {
        if ($usersWithNewEmails) {
            foreach ($usersWithNewEmails as $item) {
                /** @var EmailUser $emailUser */
                $emailUser = $item['entity'];

                $topics = $this->getTopicsForPublishing($emailUser);
                $messageData = [
                    'hasNewEmail' => array_key_exists('new', $item) === true && $item['new'] > 0 ? : false
                ];

                foreach ($topics as $topic) {
                    $this->publisher->send($topic, json_encode($messageData));
                }
            }
        }
    }

    /**
     * @param EmailUser $emailUser
     *
     * @return array
     */
    protected function getTopicsForPublishing(EmailUser $emailUser)
    {
        $topics = [];

        $organization = $emailUser->getOrganization();
        $owner = $emailUser->getOwner();

        if ($owner !== null) {
            $topics[] = self::getUserTopic($owner, $organization);
        } else {
            $em = $this->doctrine->getManager();

            $mailbox = $emailUser->getMailboxOwner();
            $authorizedUsers = $mailbox->getAuthorizedUsers();

            foreach ($authorizedUsers as $user) {
                $topics[] = self::getUserTopic($user, $organization);
            }

            $authorizedRoles = $mailbox->getAuthorizedRoles();
            foreach ($authorizedRoles as $role) {
                $users = $em->getRepository('OroUserBundle:Role')
                    ->getUserQueryBuilder($role)
                    ->getQuery()->getResult();

                foreach ($users as $user) {
                    $topics[] = self::getUserTopic($user, $organization);
                }
            }
        }

        return $topics;
    }
}
