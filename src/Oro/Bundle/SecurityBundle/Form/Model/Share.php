<?php

namespace Oro\Bundle\SecurityBundle\Form\Model;

class Share
{
    const SHARE_SCOPE_USER = 'user';
    const SHARE_SCOPE_BUSINESS_UNIT = 'business_unit';

    /** @var string */
    protected $entityClass;

    /** @var mixed */
    protected $entityId;

    /** @var array */
    protected $entities = [];

    /**
     * Get class name of the target entity
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Set class name of the target entity
     *
     * @param string $entityClass
     *
     * @return self
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Get id of the target entity
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set id of the target entity
     *
     * @param string $entityId
     *
     * @return self
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Returns array of sharing entities
     *
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * Sets array of sharing entities
     *
     * @param array $entities
     *
     * @return self
     */
    public function setEntities(array $entities)
    {
        $this->entities = $entities;

        return $this;
    }
}
