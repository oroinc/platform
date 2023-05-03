<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The base class for the JSON:API request data validators.
 */
abstract class AbstractBaseRequestDataValidator
{
    protected const ROOT_POINTER = '';

    /** @var Error[] */
    private array $errors = [];

    /**
     * @param callable $validationCallback
     *
     * @return Error[]
     */
    protected function doValidation(callable $validationCallback): array
    {
        try {
            $validationCallback();

            return $this->errors;
        } finally {
            $this->errors = [];
        }
    }

    protected function validateJsonApiSection(array $data): void
    {
        if (\array_key_exists(JsonApiDoc::JSONAPI, $data)) {
            $this->validateArray($data, JsonApiDoc::JSONAPI, self::ROOT_POINTER, false, true);
        }
    }

    protected function validateMetaSection(array $data, string $pointer = self::ROOT_POINTER): void
    {
        if (\array_key_exists(JsonApiDoc::META, $data)) {
            $this->validateArray($data, JsonApiDoc::META, $pointer, false, true);
        }
    }

    protected function validateLinksSection(array $data, string $pointer = self::ROOT_POINTER): void
    {
        if (\array_key_exists(JsonApiDoc::LINKS, $data)) {
            $this->validateArray($data, JsonApiDoc::LINKS, $pointer, false, true);
        }
    }

    protected function validateSectionNotExist(array $data, string $section): void
    {
        if (\array_key_exists($section, $data)) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $section),
                sprintf('The \'%s\' section should not exist', $section)
            );
        }
    }

    protected function validateTypeAndIdAreRequiredNotBlankString(array $data, string $pointer): bool
    {
        $isValid = true;
        if (!$this->validateRequiredNotBlankString($data, JsonApiDoc::TYPE, $pointer)) {
            $isValid = false;
        }
        if (!$this->validateRequiredNotBlankString($data, JsonApiDoc::ID, $pointer)) {
            $isValid = false;
        }

        return $isValid;
    }

    protected function validateRequired(array $data, string $property, string $pointer): bool
    {
        $isValid = true;
        if (!\array_key_exists($property, $data)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property is required', $property)
            );
            $isValid = false;
        }

        return $isValid;
    }

    protected function validateRequiredNotBlankString(array $data, string $property, string $pointer): bool
    {
        return
            $this->validateRequired($data, $property, $pointer)
            && $this->validateNotBlankString($data, $property, $pointer);
    }

    protected function validateNotBlankString(array $data, string $property, string $pointer): bool
    {
        $isValid = true;
        if (\array_key_exists($property, $data)) {
            $value = $data[$property];
            if (null === $value) {
                $this->addError(
                    $this->buildPointer($pointer, $property),
                    sprintf('The \'%s\' property should not be null', $property)
                );
                $isValid = false;
            } elseif (!\is_string($value)) {
                $this->addError(
                    $this->buildPointer($pointer, $property),
                    sprintf('The \'%s\' property should be a string', $property)
                );
                $isValid = false;
            } elseif ('' === trim($value)) {
                $this->addError(
                    $this->buildPointer($pointer, $property),
                    sprintf('The \'%s\' property should not be blank', $property)
                );
                $isValid = false;
            }
        }

        return $isValid;
    }

    protected function validateArray(
        array $data,
        string $property,
        string $pointer,
        bool $notEmpty = false,
        bool $associative = false
    ): bool {
        $isValid = true;
        $value = $data[$property];
        if (!\is_array($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should be an array', $property)
            );
            $isValid = false;
        } elseif ($notEmpty && empty($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should not be empty', $property)
            );
            $isValid = false;
        } elseif ($associative && !empty($value) && !ArrayUtil::isAssoc($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                sprintf('The \'%s\' property should be an associative array', $property)
            );
            $isValid = false;
        }

        return $isValid;
    }

    protected function buildPointer(string $parentPointer, string $property): string
    {
        return $parentPointer . '/' . $property;
    }

    protected function addError(string $pointer, string $message): void
    {
        $this->addErrorObject(
            Error::createValidationError(Constraint::REQUEST_DATA, $message)
                ->setSource(ErrorSource::createByPointer($pointer))
        );
    }

    protected function addErrorObject(Error $error): void
    {
        $this->errors[] = $error;
    }
}
