<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms entity changesets by enriching them with corresponding entity references.
 */
class EntityChangesetTransformer implements DataTransformerInterface
{
    public const ENTITY_KEY = 'entity';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $class;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $class
     */
    public function __construct(DoctrineHelper $doctrineHelper, $class)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->class = $class;
    }

    #[\Override]
    public function transform($value): mixed
    {
        return $value;
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        if (!$value) {
            return new ArrayCollection();
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array');
        }

        foreach ($value as $id => $changeSetRow) {
            $value[$id] = array_merge(
                $changeSetRow,
                [self::ENTITY_KEY => $this->getEntityById($id)]
            );
        }

        return $value;
    }

    /**
     * @param int|string $id
     * @return object
     */
    protected function getEntityById($id)
    {
        return $this->doctrineHelper->getEntityReference($this->class, $id);
    }
}
