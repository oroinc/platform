<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

class MultipleEntityAttributeNormalizer implements AttributeNormalizer
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param ManagerRegistry $registry
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ManagerRegistry $registry, DoctrineHelper $doctrineHelper)
    {
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(Workflow $workflow, Attribute $attribute, $attributeValue)
    {
        if (null === $attributeValue) {
            return null;
        }

        $this->validateAttributeValue($workflow, $attribute, $attributeValue);

        $result = [];
        foreach ($attributeValue as $value) {
            $result[] = $this->doctrineHelper->getEntityIdentifier($value);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(Workflow $workflow, Attribute $attribute, $attributeValue)
    {
        if (null === $attributeValue || !is_array($attributeValue)) {
            return null;
        }

        $em = $this->getEntityManager($workflow, $attribute);

        $result = [];
        foreach ($attributeValue as $value) {
            $result[] = $em->getReference($attribute->getOption('class'), $value);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(Workflow $workflow, Attribute $attribute, $attributeValue)
    {
        return $attribute->getType() == 'entity' && $attribute->getOption('multiple');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(Workflow $workflow, Attribute $attribute, $attributeValue)
    {
        return $attribute->getType() == 'entity' && $attribute->getOption('multiple');
    }

    /**
     * Returns EntityManager for entity.
     *
     * @param Workflow $workflow
     * @param Attribute $attribute
     * @param mixed $attributeValue
     * @throws SerializerException
     */
    protected function validateAttributeValue(Workflow $workflow, Attribute $attribute, $attributeValue)
    {
        if (!is_array($attributeValue) && !$attributeValue instanceof \Traversable) {
            throw new SerializerException(
                sprintf(
                    'Attribute "%s" of workflow "%s" must be a collection or an array, but "%s" given',
                    $attribute->getName(),
                    $workflow->getName(),
                    is_object($attributeValue) ? get_class($attributeValue) : gettype($attributeValue)
                )
            );
        }

        $expectedType = $attribute->getOption('class');
        foreach ($attributeValue as $value) {
            if (!$value instanceof $expectedType) {
                throw new SerializerException(
                    sprintf(
                        'Each value of attribute "%s" of workflow "%s" must be an instance of "%s", but "%s" found',
                        $attribute->getName(),
                        $workflow->getName(),
                        $expectedType,
                        is_object($value) ? get_class($value) : gettype($value)
                    )
                );
            }
        }
    }

    /**
     * Returns EntityManager for entity.
     *
     * @param Workflow $workflow
     * @param Attribute $attribute
     * @return EntityManager
     * @throws SerializerException
     */
    protected function getEntityManager(Workflow $workflow, Attribute $attribute)
    {
        $entityClass = $attribute->getOption('class');
        $result = $this->registry->getManagerForClass($entityClass);
        if (!$result) {
            throw new SerializerException(
                sprintf(
                    'Attribute "%s" of workflow "%s" contains object of "%s", but it\'s not managed entity class',
                    $attribute->getName(),
                    $workflow->getName(),
                    $entityClass
                )
            );
        }

        return $result;
    }
}
