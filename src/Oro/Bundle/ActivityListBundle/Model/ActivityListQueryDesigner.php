<?php

namespace Oro\Bundle\ActivityListBundle\Model;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

class ActivityListQueryDesigner extends AbstractQueryDesigner
{
    /** @var string */
    protected $entity;

    /** @var string */
    protected $definition;

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
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }
}
