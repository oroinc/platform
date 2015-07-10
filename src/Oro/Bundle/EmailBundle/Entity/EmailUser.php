<?php

namespace Oro\Bundle\EmailBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * EmailUser
 *
 * @ORM\Table(
 *      name="oro_email_user"
 * )
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\EmailUserRepository")
 */
class EmailUser extends EmailOwner
{

    /**
     * @var OrganizationInterface
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     * @JMS\Exclude
     */
    protected $owner;

    /**
     * Get organization
     *
     * @return OrganizationInterface
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param OrganizationInterface $organization
     *
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get owning user
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set owning user
     *
     * @param User $owningUser
     *
     * @return $this
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

        return $this;
    }
}
