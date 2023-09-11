<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * Provides functionality to build criteria that is used to find an entity based on the "upsert" meta option
 * when this option contains the list of field names.
 */
class UpsertCriteriaBuilder
{
    private ValueNormalizer $valueNormalizer;

    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    public function getUpsertFindEntityCriteria(
        EntityMetadata $metadata,
        array $identityFieldNames,
        array $entityData,
        string $upsertFlagPointer,
        FormContext $context
    ): ?array {
        $criteria = [];
        $hasErrors = false;
        foreach ($identityFieldNames as $fieldName) {
            $associationMetadata = $metadata->getAssociation($fieldName);
            if (null !== $associationMetadata && $associationMetadata->isCollection()) {
                $hasErrors = true;
                self::addUpsertFlagValidationError(
                    $context,
                    $upsertFlagPointer,
                    sprintf('The "%s" field is not allowed because it is to-many association.', $fieldName)
                );
                continue;
            }

            if (\array_key_exists($fieldName, $entityData)) {
                $fieldValue = $entityData[$fieldName];
                if (null !== $associationMetadata) {
                    $criteria[$fieldName] = $this->normalizeUpsertFindEntityCriteriaValue(
                        $context,
                        $fieldValue['id'],
                        $associationMetadata->getDataType()
                    );
                } else {
                    $fieldMetadata = $metadata->getField($fieldName);
                    if (null !== $fieldMetadata) {
                        $criteria[$fieldName] = $this->normalizeUpsertFindEntityCriteriaValue(
                            $context,
                            $fieldValue,
                            $fieldMetadata->getDataType()
                        );
                    } else {
                        $hasErrors = true;
                        self::addUpsertFlagValidationError(
                            $context,
                            $upsertFlagPointer,
                            sprintf('The "%s" field is unknown.', $fieldName)
                        );
                    }
                }
            } else {
                $hasErrors = true;
                self::addUpsertFlagValidationError(
                    $context,
                    $upsertFlagPointer,
                    sprintf('The "%s" field does not exist in the request data.', $fieldName)
                );
            }
        }

        if ($hasErrors) {
            return null;
        }

        return $criteria;
    }

    private function normalizeUpsertFindEntityCriteriaValue(
        FormContext $context,
        mixed $value,
        string $dataType
    ): mixed {
        if (null === $value) {
            return null;
        }

        return $this->valueNormalizer->normalizeValue($value, $dataType, $context->getRequestType());
    }

    private static function addUpsertFlagValidationError(
        FormContext $context,
        string $upsertFlagPointer,
        string $detail
    ): void {
        $context->addError(
            Error::createValidationError(Constraint::VALUE, $detail)
                ->setSource(ErrorSource::createByPointer($upsertFlagPointer))
        );
    }
}
