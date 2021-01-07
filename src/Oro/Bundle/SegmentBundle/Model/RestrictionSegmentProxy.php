<?php

namespace Oro\Bundle\SegmentBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * This class is used by DynamicSegmentQueryBuilder to convert a segment to an ORM query.
 */
class RestrictionSegmentProxy extends AbstractQueryDesigner implements SegmentIdentityAwareInterface
{
    /** @var Segment */
    private $segment;

    /** @var EntityManagerInterface */
    private $em;

    /** @var array|null */
    private $preparedDefinition;

    /**
     * @param Segment                $segment
     * @param EntityManagerInterface $em
     */
    public function __construct(Segment $segment, EntityManagerInterface $em)
    {
        $this->segment = $segment;
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getSegmentId(): ?int
    {
        return $this->segment->getId();
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
                throw new InvalidConfigurationException('Invalid definition given');
            }

            $classMetadata = $this->em->getClassMetadata($this->getEntity());
            $identifiers = $classMetadata->getIdentifier();

            // only not composite identifiers are supported
            $identifier = reset($identifiers);
            $required['columns'][] = ['name' => $identifier, 'distinct' => true];

            $decoded = array_merge_recursive($decoded, $required);

            $this->preparedDefinition = json_encode($decoded);
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
