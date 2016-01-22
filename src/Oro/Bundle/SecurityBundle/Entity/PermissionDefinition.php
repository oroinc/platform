<?php

namespace Oro\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table("oro_security_perm_definition")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class PermissionDefinition
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255)
     */
    protected $label;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }
    /**
     * @param PermissionDefinition $definition
     * @return PermissionDefinition
     */
    public function import(PermissionDefinition $definition)
    {
        // enabled flag should not be imported
        $this->setName($definition->getName())
            ->setLabel($definition->getLabel());

        return $this;
    }
}
