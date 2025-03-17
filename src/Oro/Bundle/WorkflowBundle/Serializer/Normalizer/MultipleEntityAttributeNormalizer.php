<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Model\ParameterInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

/**
 * Normalizes multiple entity attributes.
 */
class MultipleEntityAttributeNormalizer implements AttributeNormalizer
{
    public function __construct(
        protected DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function normalize(Workflow $workflow, ParameterInterface $attribute, $attributeValue): ?array
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

    #[\Override]
    public function denormalize(Workflow $workflow, ParameterInterface $attribute, $attributeValue): ?array
    {
        if (!\is_array($attributeValue)) {
            return null;
        }

        $em = $this->getEntityManager($workflow, $attribute);

        $result = [];
        foreach ($attributeValue as $value) {
            $result[] = $em->getReference($attribute->getOption('class'), $value);
        }

        return $result;
    }

    #[\Override]
    public function supportsNormalization(Workflow $workflow, ParameterInterface $attribute, $attributeValue): bool
    {
        return $attribute->getType() === 'entity' && $attribute->getOption('multiple');
    }

    #[\Override]
    public function supportsDenormalization(Workflow $workflow, ParameterInterface $attribute, $attributeValue): bool
    {
        return $attribute->getType() === 'entity' && $attribute->getOption('multiple');
    }

    protected function validateAttributeValue(
        Workflow $workflow,
        ParameterInterface $attribute,
        mixed $attributeValue
    ): void {
        if (!\is_array($attributeValue) && !$attributeValue instanceof \Traversable) {
            throw new SerializerException(\sprintf(
                'Attribute "%s" of workflow "%s" must be a collection or an array, but "%s" given',
                $attribute->getName(),
                $workflow->getName(),
                get_debug_type($attributeValue)
            ));
        }

        $expectedType = $attribute->getOption('class');
        foreach ($attributeValue as $value) {
            if (!$value instanceof $expectedType) {
                throw new SerializerException(\sprintf(
                    'Each value of attribute "%s" of workflow "%s" must be an instance of "%s", but "%s" found',
                    $attribute->getName(),
                    $workflow->getName(),
                    $expectedType,
                    get_debug_type($value)
                ));
            }
        }
    }

    protected function getEntityManager(Workflow $workflow, ParameterInterface $attribute): EntityManagerInterface
    {
        $entityClass = $attribute->getOption('class');
        $result = $this->doctrineHelper->getEntityManagerForClass($entityClass);
        if (!$result) {
            throw new SerializerException(\sprintf(
                'Attribute "%s" of workflow "%s" contains object of "%s", but it\'s not managed entity class',
                $attribute->getName(),
                $workflow->getName(),
                $entityClass
            ));
        }

        return $result;
    }
}
