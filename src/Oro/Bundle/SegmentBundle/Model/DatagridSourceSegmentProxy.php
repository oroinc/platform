<?php

namespace Oro\Bundle\SegmentBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

/**
 * Class DatagridSourceSegmentProxy
 *
 * @package Oro\Bundle\SegmentBundle\Model
 *
 *  This class is used by SegmentDatagridConfigurationBuilder to prevent converting definition for filters.
 *  It replaces all existing filters by "segment" filter, all segment restrictions will be applied there.
 *  It's only used when need to build segment's datagrid representation
 */
class DatagridSourceSegmentProxy extends AbstractQueryDesigner
{
    /** @var Segment */
    protected $segment;

    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var array|null */
    protected $preparedDefinition;

    /**
     * Constructor
     *
     * @param Segment                     $segment
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(Segment $segment, EntityManager $em)
    {
        $this->segment = $segment;
        $this->em      = $em;
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
    public function getDefinition()
    {
        if (null === $this->preparedDefinition) {
            $definition = $this->segment->getDefinition();

            $decoded = json_decode($definition, true);
            if (null === $decoded) {
                throw new \LogicException('Invalid definition given');
            }

            $classMetadata = $this->em->getClassMetadata($this->getEntity());
            $identifiers   = $classMetadata->getIdentifier();

            // only not composite identifiers are supported
            $identifier = reset($identifiers);

            $this->preparedDefinition = array_merge(
                $decoded,
                [
                    'filters' => [
                        [
                            'columnName' => $identifier,
                            'criterion'  =>
                                [
                                    'filter' => 'segment',
                                    'data'   => ['value' => $this->segment->getId()]
                                ],
                        ]
                    ]
                ]
            );
            $this->preparedDefinition = json_encode($this->preparedDefinition);
        }

        return $this->preparedDefinition;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefinition($definition)
    {
        $this->segment->setDefinition($definition);
    }
}
