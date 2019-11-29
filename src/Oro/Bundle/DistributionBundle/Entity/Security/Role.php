<?php

namespace Oro\Bundle\DistributionBundle\Entity\Security;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\Role as BaseRole;

/**
 * Role Entity
 *
 * @ORM\Entity()
 * @ORM\Table(name="oro_access_role")
 */
class Role extends BaseRole
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="smallint", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, length=30, nullable=false)
     */
    protected $role;

    /**
     * Populate the role field
     *
     * @param string $role ROLE_FOO etc
     */
    public function __construct(string $role = '')
    {
        parent::__construct($role);

        $this->role = $role;
    }

    /**
     * Return the role name field
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Return the role name field
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->role;
    }
}
