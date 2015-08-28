<?php

namespace Oro\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This entity class intended to allow usage of basic acl_object_identity_ancestors table in DQL.
 * The main goal of this approach is possibility to use this entity in AclWalker to determine shared records.
 *
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(
 *      name="acl_object_identity_ancestors",
 *      indexes={
 *          @ORM\Index(name="IDX_825DE2993D9AB4A6", columns={"object_identity_id"}),
 *          @ORM\Index(name="IDX_825DE299C671CEA1", columns={"ancestor_id"})
 *      }
 * )
 */
class AclObjectIdentityAncestor
{
    /**
     * @var AclObjectIdentity
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AclObjectIdentity")
     * @ORM\JoinColumn(name="object_identity_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $objectIdentity;

    /**
     * @var AclObjectIdentity
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AclObjectIdentity")
     * @ORM\JoinColumn(name="ancestor_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $ancestor;

    /**
     * Sets objectIdentity
     *
     * @param AclObjectIdentity $objectIdentity
     * @return self
     */
    public function setObjectIdentity(AclObjectIdentity $objectIdentity)
    {
        $this->objectIdentity = $objectIdentity;

        return $this;
    }

    /**
     * Gets objectIdentity
     *
     * @return AclObjectIdentity
     */
    public function getObjectIdentity()
    {
        return $this->objectIdentity;
    }

    /**
     * Sets ancestor
     *
     * @param AclObjectIdentity $ancestor
     * @return self
     */
    public function setAncestor(AclObjectIdentity $ancestor)
    {
        $this->ancestor = $ancestor;

        return $this;
    }

    /**
     * Gets ancestor
     *
     * @return AclObjectIdentity
     */
    public function getAncestor()
    {
        return $this->ancestor;
    }
}
