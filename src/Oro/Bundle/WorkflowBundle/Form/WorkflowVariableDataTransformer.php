<?php

namespace Oro\Bundle\WorkflowBundle\Form;

use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transformer for variable data.
 */
class WorkflowVariableDataTransformer implements DataTransformerInterface
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var Variable|null */
    protected $variable;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param Variable $variable
     */
    public function __construct(ManagerRegistry $managerRegistry, Variable $variable = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->variable = $variable;
    }

    /**
     * @param mixed $entity
     *
     * @return mixed
     */
    public function transform($entity)
    {
        return $entity;
    }

    /**
     * @param mixed $entity
     *
     * @return string
     */
    public function reverseTransform($entity)
    {
        $class = is_object($entity) ? get_class($entity) : null;
        $metadata = $this->getMetadataForClass($class);
        if (!$metadata) {
            return '';
        }

        $identifier = null;
        if ($this->variable instanceof Variable) {
            $identifier = $this->variable->getOption('identifier');
        }

        if (!$identifier) {
            $identifierFields = $metadata->getIdentifierFieldNames();
            if (!isset($identifierFields[0])) {
                return '';
            }
            $identifier = $identifierFields[0];
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        try {
            return $accessor->getValue($entity, $identifier);
        } catch (\RuntimeException $e) {
            return '';
        }
    }

    /**
     * @param null|string $class
     * @return null|ClassMetadata
     */
    protected function getMetadataForClass($class)
    {
        try {
            $entityManager = $this->managerRegistry->getManagerForClass($class);
        } catch (ORMException $e) {
            return null;
        }

        return $entityManager ? $entityManager->getClassMetadata($class) : null;
    }
}
