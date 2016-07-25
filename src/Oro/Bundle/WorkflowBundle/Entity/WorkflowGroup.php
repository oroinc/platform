<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(
 *      name="oro_workflow_group",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_workflow_group_unique_idx", columns={"type", "name"})
 *      }
 * )
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "note"={
 *              "immutable"=true
 *          },
 *          "comment"={
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
class WorkflowGroup
{
    const TYPE_EXCLUSIVE_ACTIVE = 10;
    const TYPE_EXCLUSIVE_RECORD = 20;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var Collection|WorkflowDefinition[]
     *
     * @ORM\ManyToMany(targetEntity="WorkflowDefinition", mappedBy="groups")
     */
    protected $definitions;

    public function __construct()
    {
        $this->definitions = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Collection|WorkflowDefinition[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @param Collection|WorkflowDefinition[] $definitions
     * @return $this
     */
    public function setDefinitions($definitions)
    {
        $this->definitions = $definitions;

        return $this;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return $this
     */
    public function addDefinition(WorkflowDefinition $definition)
    {
        if (!$this->definitions->contains($definition)) {
            $this->definitions->add($definition);
        }

        return $this;
    }
}
