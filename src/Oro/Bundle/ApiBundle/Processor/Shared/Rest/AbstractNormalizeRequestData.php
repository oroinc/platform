<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The base class for processors that prepare REST API request data to be processed by Symfony Forms.
 */
abstract class AbstractNormalizeRequestData implements ProcessorInterface
{
    protected EntityIdTransformerRegistry $entityIdTransformerRegistry;
    protected ?FormContext $context = null;
    protected ?string $requestDataItemKey = null;

    public function __construct(EntityIdTransformerRegistry $entityIdTransformerRegistry)
    {
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    protected function normalizeData(array $data, EntityMetadata $metadata): array
    {
        $fieldNames = array_keys($data);
        foreach ($fieldNames as $fieldName) {
            $associationMetadata = $metadata->getAssociation($fieldName);
            if (null !== $associationMetadata) {
                $targetEntityClass = $associationMetadata->getTargetClassName();
                $targetMetadata = $associationMetadata->getTargetMetadata();
                if ($associationMetadata->isCollection()) {
                    foreach ($data[$fieldName] as $key => &$value) {
                        $value = $this->normalizeRelationId(
                            $fieldName . '.' . $key,
                            $targetEntityClass,
                            $value,
                            $targetMetadata
                        );
                    }
                } elseif (null === $data[$fieldName]) {
                    $data[$fieldName] = [];
                } else {
                    $data[$fieldName] = $this->normalizeRelationId(
                        $fieldName,
                        $targetEntityClass,
                        $data[$fieldName],
                        $targetMetadata
                    );
                }
            }
        }

        return $data;
    }

    /**
     * @param string         $propertyPath
     * @param string         $entityClass
     * @param mixed          $entityId
     * @param EntityMetadata $metadata
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeRelationId(
        string $propertyPath,
        string $entityClass,
        mixed $entityId,
        EntityMetadata $metadata
    ): array {
        return [
            'class' => $entityClass,
            'id'    => $this->normalizeEntityId($propertyPath, $entityId, $metadata)
        ];
    }

    protected function normalizeEntityId(string $propertyPath, mixed $entityId, EntityMetadata $metadata): mixed
    {
        try {
            $normalizedId = $this->getEntityIdTransformer($this->context->getRequestType())
                ->reverseTransform($entityId, $metadata);
            if (null === $normalizedId) {
                $this->context->addNotResolvedIdentifier(
                    'requestData'
                    . (null !== $this->requestDataItemKey ? '.' . $this->requestDataItemKey : '')
                    . ConfigUtil::PATH_DELIMITER
                    . $propertyPath,
                    new NotResolvedIdentifier($entityId, $metadata->getClassName())
                );
            }

            return $normalizedId;
        } catch (\Exception $e) {
            $this->addValidationError(Constraint::ENTITY_ID, $propertyPath)
                ->setInnerException($e);
        }

        return $entityId;
    }

    protected function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }

    protected function addValidationError(string $title, string $propertyPath = null): Error
    {
        $error = Error::createValidationError($title);
        if (null !== $propertyPath) {
            $error->setSource(ErrorSource::createByPropertyPath(
                (null !== $this->requestDataItemKey ? $this->requestDataItemKey . '.' : '') . $propertyPath
            ));
        }
        $this->context->addError($error);

        return $error;
    }
}
