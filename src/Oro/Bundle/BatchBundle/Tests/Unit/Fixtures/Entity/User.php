<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $username;

    /**
     * @ORM\ManyToOne(targetEntity="BusinessUnit", cascade={"persist"})
     * @ORM\JoinColumn(name="business_unit_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @ORM\ManyToMany(targetEntity="BusinessUnit", inversedBy="users")
     * @ORM\JoinTable(name="oro_user_business_unit",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="business_unit_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $businessUnits;

    /**
     * @ORM\ManyToMany(targetEntity="Organization", inversedBy="users")
     * @ORM\JoinTable(name="oro_user_organization",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $organizations;

    /**
     * @ORM\ManyToOne(targetEntity="Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="oro_user_access_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $groups;

    /**
     * @ORM\ManyToMany(targetEntity="Role")
     * @ORM\JoinTable(name="oro_user_access_role",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $roles;

    /**
     * @ORM\OneToMany(targetEntity="UserApi", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $apiKeys;

    /**
     * @ORM\OneToMany(targetEntity="Status", mappedBy="user")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    protected $statuses;

    /**
     * @ORM\OneToMany(targetEntity="EmailOrigin", mappedBy="owner", cascade={"persist", "remove"})
     */
    protected $emailOrigins;

    /**
     * @ORM\OneToMany(targetEntity="UserEmail", mappedBy="user", orphanRemoval=true, cascade={"persist"})
     */
    protected $emails;
}
