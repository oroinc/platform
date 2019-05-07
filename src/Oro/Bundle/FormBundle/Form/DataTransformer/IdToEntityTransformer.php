<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms between id and entity.
 */
class IdToEntityTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var string
     */
    private $className;

    /**
     * @param ManagerRegistry $registry
     * @param string $className
     */
    public function __construct(ManagerRegistry $registry, string $className)
    {
        $this->registry = $registry;
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return null;
        }

        return $this->getObjectManager()
            ->find($this->className, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!is_object($value) || !is_a($value, $this->className, true)) {
            return null;
        }

        $identifiers = $this->getObjectManager()
            ->getClassMetadata($this->className)
            ->getIdentifierValues($value);

        if (count($identifiers) > 1) {
            return null;
        }

        return reset($identifiers);
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return $this->registry->getManagerForClass($this->className);
    }
}
