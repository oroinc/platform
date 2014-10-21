<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_integration_change_set")
 * @ORM\Entity()
 */
class ChangeSet
{
    const TYPE_LOCAL = 'localChanges';
    const TYPE_REMOTE = 'remoteChanges';

    /**
     * @var array
     */
    public static $types = [
        self::TYPE_LOCAL,
        self::TYPE_REMOTE
    ];

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="entity_class", type="string", unique=false, length=255, nullable=false)
     */
    protected $entityClass;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=false)
     */
    protected $entityId;

    /**
     * @var array
     *
     * @ORM\Column(name="local_changes", type="array")
     */
    protected $localChanges = [];

    /**
     * @var array
     *
     * @ORM\Column(name="remote_changes", type="array")
     */
    protected $remoteChanges = [];

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set entityClass
     *
     * @param string $entityClass
     * @return ChangeSet
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Get entityClass
     *
     * @return string 
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Set entityId
     *
     * @param integer $entityId
     * @return ChangeSet
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get entityId
     *
     * @return integer 
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set localChanges
     *
     * @param array $localChanges
     * @return ChangeSet
     */
    public function setLocalChanges(array $localChanges)
    {
        $this->localChanges = $localChanges;

        return $this;
    }

    /**
     * Get localChanges
     *
     * @return array 
     */
    public function getLocalChanges()
    {
        return $this->localChanges;
    }

    /**
     * Set remoteChanges
     *
     * @param array $remoteChanges
     * @return ChangeSet
     */
    public function setRemoteChanges(array $remoteChanges)
    {
        $this->remoteChanges = $remoteChanges;

        return $this;
    }

    /**
     * Get remoteChanges
     *
     * @return array 
     */
    public function getRemoteChanges()
    {
        return $this->remoteChanges;
    }
}
