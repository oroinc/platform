<?php

namespace Oro\Bundle\EntityBundle\Grid;

use Oro\Bundle\EntityBundle\Provider\EntityProvider;

class GridHelper
{
    /**
     * @var EntityProvider
     */
    protected $entityProvider;

    /**
     * @var array
     */
    protected $entityNames;

    /**
     * Constructor
     *
     * @param EntityProvider $entityProvider
     */
    public function __construct(EntityProvider $entityProvider)
    {
        $this->entityProvider = $entityProvider;
    }

    /**
     * Gets names of all configurable entities
     *
     * @return array
     *      key   => full class name of an entity
     *      value => translated entity name
     */
    public function getEntityNames()
    {
        if (!$this->entityNames) {
            $this->entityNames = [];
            foreach ($this->entityProvider->getEntities() as $entity) {
                $this->entityNames[$entity['name']] = $entity['label'];
            }
        }

        return $this->entityNames;
    }
}
