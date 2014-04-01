<?php

namespace Oro\Bundle\SegmentBundle\Model;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

abstract class AbstractSegmentProxy extends AbstractQueryDesigner
{
    /** @var Segment */
    protected $segment;

    /** @var array|null */
    protected $preparedDefinition;

    /**
     * Constructor
     *
     * @param Segment $segment
     */
    public function __construct(Segment $segment)
    {
        $this->segment = $segment;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this->segment->getEntity();
    }

    /**
     * {@inheritdoc}
     */
    public function setEntity($entity)
    {
        $this->segment->setEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefinition($definition)
    {
        $this->segment->setDefinition($definition);
    }
}
