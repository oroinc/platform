<?php

namespace Oro\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This entity class intended to allow usage of basic acl_classes table in DQL. The main goal of this approach
 * is possibility to use this entity in AclWalker to determine shared records.
 *
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(
 *      name="acl_classes",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="UNIQ_69DD750638A36066", columns={"class_type"})}
 * )
 */
class AclClass
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
     * @ORM\Column(name="class_type", type="string", length=200)
     */
    protected $classType;

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
     * Sets classType
     *
     * @param string $classType
     * @return self
     */
    public function setClassType($classType)
    {
        $this->classType = $classType;

        return $this;
    }

    /**
     * Gets classType
     *
     * @return string
     */
    public function getClassType()
    {
        return $this->classType;
    }
}
