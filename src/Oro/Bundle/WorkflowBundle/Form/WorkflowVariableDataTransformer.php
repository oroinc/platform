<?php

namespace Oro\Bundle\WorkflowBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\ORMException;

use Oro\Bundle\WorkflowBundle\Model\Variable;

class WorkflowVariableDataTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var Variable|null
     */
    private $variable;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param Variable $variable
     *
     * @return $this
     */
    public function setVariable(Variable $variable)
    {
        $this->variable = $variable;

        return $this;
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
            $identifier = $this->variable->getOption('identifier', null);
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
