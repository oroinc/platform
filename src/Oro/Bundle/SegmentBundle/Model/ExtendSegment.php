<?php

namespace Oro\Bundle\SegmentBundle\Model;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;


class ExtendSegment extends AbstractQueryDesigner {
    
	/**
     * Get the full name of an entity on which this segment is based
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the full name of an entity on which this segment is based
     *
     * @param string $entity
     * @return Segment
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get this segment definition in YAML format
     *
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Set this segment definition in YAML format
     *
     * @param string $definition
     * @return Segment
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }

}
