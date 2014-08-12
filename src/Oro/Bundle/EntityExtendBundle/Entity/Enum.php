<?php

namespace Oro\Bundle\EntityExtendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="oro_enum",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_enum_uq", columns={"code"})
 *      }
 * )
 * @ORM\Entity()
 * @Config(
 *      mode="readonly",
 *      defaultValues={
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class Enum
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=21)
     */
    protected $code;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", options={"default"=false})
     */
    protected $public = false;

    /**
     * @param string $code The enum code
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param boolean $public
     *
     * @return Enum
     */
    public function setPublic($public)
    {
        $this->public = (bool)$public;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * Get a human-readable representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->code;
    }
}
