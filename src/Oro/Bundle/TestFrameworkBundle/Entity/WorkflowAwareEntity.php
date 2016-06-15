<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowAwareTrait;

/**
 * @ORM\Table(name="test_workflow_aware_entity")
 * @ORM\Entity
 * @Config
 */
class WorkflowAwareEntity implements TestFrameworkEntityInterface, WorkflowAwareInterface
{
    use WorkflowAwareTrait;
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return WorkflowAwareEntity
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
}
