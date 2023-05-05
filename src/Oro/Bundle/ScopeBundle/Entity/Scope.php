<?php

namespace Oro\Bundle\ScopeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Represents a set of application parameters that can be used to find application data suitable for these parameters.
 * @ORM\Table(
 *     name="oro_scope",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="oro_scope_row_hash_uidx", columns={"row_hash"})
 *     }
 * )
 * @ORM\Entity()
 * @Config()
 */
class Scope implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="row_hash", type="string", nullable=true, length=32)
     */
    private $rowHash;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRowHash()
    {
        return $this->rowHash;
    }

    /**
     * @param string $rowHash
     * @return Scope
     */
    public function setRowHash($rowHash)
    {
        $this->rowHash = $rowHash;

        return $this;
    }
}
