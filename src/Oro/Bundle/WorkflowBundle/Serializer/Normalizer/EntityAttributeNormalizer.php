<?php

namespace Oro\Bundle\WorkflowBundle\Serializer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Model\ParameterInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

/**
 * Normalizes entity attribute.
 */
class EntityAttributeNormalizer implements AttributeNormalizer
{
    public function __construct(
        protected DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function normalize(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        if (null === $attributeValue) {
            return null;
        }

        $this->validateAttributeValue($workflow, $attribute, $attributeValue);

        return $this->doctrineHelper->getEntityIdentifier($attributeValue) ?: null;
    }

    protected function validateAttributeValue(
        Workflow $workflow,
        ParameterInterface $attribute,
        mixed $attributeValue
    ): void {
        $expectedType = $attribute->getOption('class');
        if (!$attributeValue instanceof $expectedType) {
            throw new SerializerException(\sprintf(
                'Attribute "%s" of workflow "%s" must be an instance of "%s", but "%s" given',
                $attribute->getName(),
                $workflow->getName(),
                $expectedType,
                get_debug_type($attributeValue)
            ));
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

    #[\Override]
    public function denormalize(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        if (!\is_array($attributeValue)) {
            return null;
        }

        return $this->getEntityManager($workflow, $attribute)
            ->getReference($attribute->getOption('class'), $attributeValue);
    }

    #[\Override]
    public function supportsNormalization(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        return $attribute->getType() === 'entity' && !$attribute->getOption('multiple');
    }

    #[\Override]
    public function supportsDenormalization(Workflow $workflow, ParameterInterface $attribute, $attributeValue)
    {
        return $attribute->getType() === 'entity' && !$attribute->getOption('multiple');
    }
}
