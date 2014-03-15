<?php

namespace Oro\Bundle\SegmentBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;

/**
 * Class DatagridSourceSegmentProxy
 *
 * @package Oro\Bundle\SegmentBundle\Model
 *
 *  This class is used by SegmentDatagridConfigurationBuilder to prevent converting definition for filters.
 *  It replaces all existing filters by "segment" filter, all segment restrictions will be applied there.
 *  It's only used when need to build segment's datagrid representation
 */
class DatagridSourceSegmentProxy extends AbstractSegmentProxy
{
    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /**
     * {@inheritdoc}
     */
    public function __construct(Segment $segment, EntityManager $em)
    {
        parent::__construct($segment);
        $this->em = $em;
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
                throw new InvalidConfigurationException('Invalid definition given');
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
}
