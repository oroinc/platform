<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;

/**
 * The utility class to get values of "update" and "upsert" meta options.
 */
class MetaOperationParser
{
    private const ID_FIELD = 'id';

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function getOperationFlags(
        array $meta,
        string $updateFlagName,
        string $upsertFlagName,
        ?string $metaPointer = null,
        ?FormContext $context = null
    ): ?array {
        $updateFlag = null;
        if (\array_key_exists($updateFlagName, $meta)) {
            $updateFlag = self::getUpdateFlag($meta, $updateFlagName);
            if (null === $updateFlag) {
                if (null !== $context) {
                    self::addValidationError(
                        $context,
                        Constraint::VALUE,
                        'This value should be a boolean.',
                        self::buildPointer($metaPointer, $updateFlagName)
                    );
                }

                return null;
            }
        }

        $upsertFlag = null;
        if (\array_key_exists($upsertFlagName, $meta)) {
            $upsertFlag = self::getUpsertFlag($meta, $upsertFlagName);
            if (null === $upsertFlag) {
                if (null !== $context) {
                    self::addValidationError(
                        $context,
                        Constraint::VALUE,
                        'This value should be a boolean or an array of strings.',
                        self::buildPointer($metaPointer, $upsertFlagName)
                    );
                }

                return null;
            }
        }

        if ($updateFlag && $upsertFlag) {
            if (null !== $context) {
                self::addValidationError(
                    $context,
                    Constraint::REQUEST_DATA,
                    sprintf('Both "%s" and "%s" options cannot be set.', $updateFlagName, $upsertFlagName),
                    $metaPointer
                );
            }

            return null;
        }

        if (\is_array($upsertFlag) && \count($upsertFlag) === 1 && self::ID_FIELD === $upsertFlag[0]) {
            $upsertFlag = true;
        }

        return [$updateFlag, $upsertFlag];
    }

    private static function getUpdateFlag(array $meta, string $updateFlagName): ?bool
    {
        $value = $meta[$updateFlagName];
        if (false === $value || true === $value) {
            return $value;
        }

        return null;
    }

    private static function getUpsertFlag(array $meta, string $upsertFlagName): bool|array|null
    {
        $value = $meta[$upsertFlagName];
        if (false === $value || true === $value) {
            return $value;
        }
        if (\is_array($value) && self::isValidListOfFieldNames($value)) {
            return $value;
        }

        return null;
    }

    private static function isValidListOfFieldNames(array $value): bool
    {
        if (\count($value) === 0) {
            return false;
        }

        if (!array_is_list($value)) {
            return false;
        }

        foreach ($value as $val) {
            if (!\is_string($val) || '' === trim($val)) {
                return false;
            }
        }

        return true;
    }

    private static function buildPointer(string $parentPointer, string $property): string
    {
        return $parentPointer . '/' . $property;
    }

    private static function addValidationError(
        FormContext $context,
        string $title,
        string $detail,
        string $pointer
    ): void {
        $context->addError(
            Error::createValidationError($title, $detail)
                ->setSource(ErrorSource::createByPointer($pointer))
        );
    }
}
