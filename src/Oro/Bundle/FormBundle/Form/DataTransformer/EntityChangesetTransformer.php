<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class EntityChangesetTransformer implements DataTransformerInterface
{
    const ENTITY_KEY = 'entity';
    const DATA_KEY = 'data';

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
        $result = [];
        if (null === $value || [] === $value) {
            return $result;
        }

        foreach ($value as $id => $changeSetRow) {
            $result[$id] = $changeSetRow[self::DATA_KEY];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $result = new ArrayCollection();
        if (!$value) {
            return $result;
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array');
        }

        foreach ($value as $id => $changeSetRow) {
            $result->set(
                $id,
                [
                    self::ENTITY_KEY => $this->getEntityById($id),
                    self::DATA_KEY => $changeSetRow
                ]
            );
        }

        return $result;
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
