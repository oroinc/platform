<?php

namespace Oro\Bundle\NotificationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\NotificationBundle\Entity\Repository\RecipientListRepository;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Holds recipients of email notification.
 */
#[ORM\Entity(repositoryClass: RecipientListRepository::class)]
#[ORM\Table('oro_notification_recip_list')]
class RecipientList
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'oro_notification_recip_user')]
    #[ORM\JoinColumn(name: 'recipient_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $users = null;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class)]
    #[ORM\JoinTable(name: 'oro_notification_recip_group')]
    #[ORM\JoinColumn(name: 'recipient_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'group_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $groups = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $email = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'additional_email_associations', type: Types::SIMPLE_ARRAY, nullable: true)]
    protected $additionalEmailAssociations = [];

    /**
     * @var array
     */
    #[ORM\Column(name: 'entity_emails', type: Types::SIMPLE_ARRAY, nullable: true)]
    protected $entityEmails = [];

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Setter for email
     *
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Getter for email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Gets the groups related to list
     *
     * @return ArrayCollection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Add specified group
     *
     * @param Group $group
     * @return $this
     */
    public function addGroup(Group $group)
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    /**
     * Remove specified group
     *
     * @param Group $group
     * @return $this
     */
    public function removeGroup(Group $group)
    {
        if ($this->getGroups()->contains($group)) {
            $this->getGroups()->removeElement($group);
        }

        return $this;
    }

    /**
     * Add specified user
     *
     * @param User $user
     * @return $this
     */
    public function addUser(User $user)
    {
        if (!$this->getUsers()->contains($user)) {
            $this->getUsers()->add($user);
        }

        return $this;
    }

    /**
     * Remove specified user
     *
     * @param User $user
     * @return $this
     */
    public function removeUser(User $user)
    {
        if ($this->getUsers()->contains($user)) {
            $this->getUsers()->removeElement($user);
        }

        return $this;
    }

    /**
     * Getters for users
     *
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return array
     */
    public function getAdditionalEmailAssociations()
    {
        return $this->additionalEmailAssociations;
    }

    /**
     * @param array $additionalEmailAssociations
     */
    public function setAdditionalEmailAssociations($additionalEmailAssociations)
    {
        $this->additionalEmailAssociations = $additionalEmailAssociations;

        return $this;
    }

    /**
     * To string implementation
     *
     * @return string
     */
    public function __toString()
    {
        // get user emails
        $results = $this->getUsers()->map(
            function (User $user) {
                return sprintf(
                    '%s %s <%s>',
                    $user->getFirstName(),
                    $user->getLastName(),
                    $user->getEmail()
                );
            }
        )->toArray();

        $results = array_merge(
            $results,
            $this->getGroups()->map(
                function (Group $group) use (&$results) {
                    return sprintf(
                        '%s (group)',
                        $group->getName()
                    );
                }
            )->toArray()
        );

        if ($this->getEmail()) {
            $results[] = sprintf('Custom email: <%s>', $this->getEmail());
        }

        return implode(', ', $results);
    }

    /**
     * @return array
     */
    public function getEntityEmails()
    {
        return $this->entityEmails;
    }

    public function setEntityEmails(array $entityEmails)
    {
        $this->entityEmails = $entityEmails;

        return $this;
    }
}
