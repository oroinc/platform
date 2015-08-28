<?php

namespace Oro\Bundle\ReportBundle\Model;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;


class ExtendReport extends AbstractQueryDesigner {
    
	/**
     * Get the full name of an entity on which this report is based
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the full name of an entity on which this report is based
     *
     * @param string $entity
     * @return Report
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }

}
