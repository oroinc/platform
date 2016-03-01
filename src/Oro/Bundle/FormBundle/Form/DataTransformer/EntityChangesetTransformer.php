<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EntityChangesetTransformer implements DataTransformerInterface
{
    const ENTITY_KEY = 'entity';

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

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
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
