<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class State
 *
 * @package Oro\Bundle\IntegrationBundle\Entity
 * @ORM\Entity
 * @ORM\Table(
 *      name="oro_integration_entity_state",
 *      indexes={
 *          @ORM\Index(name="oro_entity_class_id_idx", columns={"entity_class", "entity_id"}),
 *          @ORM\Index(name="oro_entity_class_id_state_idx", columns={"entity_class", "entity_id", "state"})
 *      }
 * )
 */
class State
{
    const STATE_SCHEDULED_FOR_EXPORT = 1;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Channel
     *
     * @ORM\Column(name="entity_class", type="string", length=255)
     */
    protected $entityClass;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_id", type="integer")
     */
    protected $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="smallint")
     */
    protected $state;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $entityClass
     *
     * @return $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param int $entityId
     *
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param int $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }
}
