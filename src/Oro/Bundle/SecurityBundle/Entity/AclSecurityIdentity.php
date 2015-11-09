<?php

namespace Oro\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This entity class intended to allow usage of basic acl_security_identities table in DQL. The main goal
 * of this approach is possibility to use this entity in AclWalker to determine shared records.
 *
 * @ORM\Entity(
 *      readOnly=true,
 *      repositoryClass="Oro\Bundle\SecurityBundle\Entity\Repository\AclSecurityIdentityRepository"
 * )
 * @ORM\Table(
 *      name="acl_security_identities",
 *      indexes={
 *          @ORM\Index(name="acl_sids_username_idx", columns={"username"})
 *      },
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="UNIQ_8835EE78772E836AF85E0677", columns={"identifier", "username"})
 *      }
 * )
 */
class AclSecurityIdentity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", length=200)
     */
    protected $identifier;

    /**
     * @var bool
     *
     * @ORM\Column(name="username", type="boolean")
     */
    protected $username;

    /**
     * Gets id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets identifier
     *
     * @param string $identifier
     * @return self
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Gets identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets username
     *
     * @param bool $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Gets username
     *
     * @return bool
     */
    public function getUsername()
    {
        return $this->username;
    }
}
