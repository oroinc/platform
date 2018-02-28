<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\ActionBundle\Model\ParameterInterface;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class WorkflowVariableNormalizer extends WorkflowDataNormalizer
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param Workflow $workflow
     * @param ParameterInterface $variable
     * @param array $options
     *
     * @return mixed
     */
    public function normalizeVariable(Workflow $workflow, ParameterInterface $variable, array $options)
    {
        return $this->normalizeAttribute($workflow, $variable, $options['value']);
    }

    /**
     * @param Workflow $workflow
     * @param ParameterInterface $variable
     * @param array $options
     *
     * @return AttributeNormalizer
     *
     * @throws SerializerException
     */
    public function denormalizeVariable(Workflow $workflow, ParameterInterface $variable, array $options)
    {
        $type = $variable->getType();
        if (!in_array($type, ['object', 'entity'], true)) {
            // configuration is serialized with variable configuration
            if ('array' === $variable->getType()) {
                return $options['value'];
            }

            return $this->denormalizeAttribute($workflow, $variable, $options['value']);
        }

        if ('object' === $type) {
            return $this->denormalizeObjectVariable($options);
        }

        if ('entity' === $type) {
            return $this->denormalizeEntityVariable($options);
        }

        return null;
    }

    /**
     * @param string $class
     *
     * @return \Doctrine\Common\Persistence\ObjectManager|null
     * @throws SerializerException
     */
    protected function getManagerForClass($class)
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);
        if (!$entityManager) {
            throw new SerializerException(sprintf('Can\'t get entity manager for class %s', $class));
        }

        return $entityManager;
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getOption(array $options, $key, $default = null)
    {
        if (isset($options[$key])) {
            return $options[$key];
        }

        return $default;
    }

    /**
     * @param array $options
     *
     * @return mixed
     */
    private function denormalizeObjectVariable(array $options)
    {
        $class = $this->getOption($options['options'], 'class');
        $propertyPath = $this->getOption($options, 'property_path');

        try {
            if ($propertyPath) {
                $object = new $class();
                $object->{$propertyPath} = $options['value'];
            } else {
                $object = new $class($options['value']);
            }

            return $object;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param array $options
     *
     * @return mixed
     * @throws SerializerException
     */
    private function denormalizeEntityVariable(array $options)
    {
        $class = $this->getOption($options['options'], 'class');
        $manager = $this->getManagerForClass($class);
        if (!$manager) {
            throw new SerializerException(sprintf('Can\'t get entity manager for class %s', $class));
        }

        $identifier = $this->getOption($options['options'], 'identifier');
        if (!$identifier) {
            return $manager->find($class, $options['value']);
        }

        /** @var ClassMetadataInfo $metadata */
        $metadata = $manager->getClassMetadata($class);
        if ($metadata->isIdentifierComposite) {
            throw new SerializerException(sprintf(
                'Entity with class %s has a composite identifier',
                $class
            ));
        }
        if (!$metadata->isUniqueField($identifier) && !in_array($identifier, $metadata->getIdentifier(), true)) {
            throw new SerializerException(sprintf(
                'Field %s is not unique in entity with class %s',
                $identifier,
                $class
            ));
        }

        $repository = $manager->getRepository($class);

        return $repository->findOneBy([$identifier => $options['value']]);
    }
}
