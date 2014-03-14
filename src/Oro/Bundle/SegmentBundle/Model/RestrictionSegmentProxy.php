<?php

namespace Oro\Bundle\SegmentBundle\Model;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Class RestrictionSegmentProxy
 *
 * @package Oro\Bundle\SegmentBundle\Model
 *
 */
class RestrictionSegmentProxy extends AbstractSegmentProxy
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
                throw new \LogicException('Invalid definition given');
            }

            $classMetadata = $this->em->getClassMetadata($this->getEntity());
            $identifiers   = $classMetadata->getIdentifier();

            // only not composite identifiers are supported
            $identifier = reset($identifiers);

            $this->preparedDefinition = array_merge(
                $decoded,
                [
                    'columns' => [
                        ['name' => $identifier]
                    ]
                ]
            );
            $this->preparedDefinition = json_encode($this->preparedDefinition);
        }

        return $this->preparedDefinition;
    }
}
