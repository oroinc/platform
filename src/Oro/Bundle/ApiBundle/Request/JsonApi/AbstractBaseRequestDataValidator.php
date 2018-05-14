<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The base class for the JSON.API request data validators.
 */
abstract class AbstractBaseRequestDataValidator
{
    protected const ROOT_POINTER = '';

    /** @var Error[] */
    private $errors = [];

    /**
     * @param callable $validationCallback
     *
     * @return Error[]
     */
    protected function doValidation($validationCallback): array
    {
        try {
            $validationCallback();

            return $this->errors;
        } finally {
            $this->errors = [];
        }
    }

    /**
     * @param array  $data
     * @param string $section
     */
    protected function validateSectionNotExist(array $data, string $section): void
    {
        if (\array_key_exists($section, $data)) {
            $this->addError(
                $this->buildPointer(self::ROOT_POINTER, $section),
                \sprintf('The \'%s\' section should not exist', $section)
            );
        }
    }

    /**
     * @param array  $data
     * @param string $pointer
     *
     * @return bool
     */
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

    /**
     * @param array  $data
     * @param string $property
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateRequired(array $data, string $property, string $pointer): bool
    {
        $isValid = true;
        if (!\array_key_exists($property, $data)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                \sprintf('The \'%s\' property is required', $property)
            );
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param array  $data
     * @param string $property
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateRequiredNotBlankString(array $data, string $property, string $pointer): bool
    {
        return
            $this->validateRequired($data, $property, $pointer)
            && $this->validateNotBlankString($data, $property, $pointer);
    }

    /**
     * @param array  $data
     * @param string $property
     * @param string $pointer
     *
     * @return bool
     */
    protected function validateNotBlankString(array $data, string $property, string $pointer): bool
    {
        $isValid = true;
        if (\array_key_exists($property, $data)) {
            $value = $data[$property];
            if (null === $value) {
                $this->addError(
                    $this->buildPointer($pointer, $property),
                    \sprintf('The \'%s\' property should not be null', $property)
                );
                $isValid = false;
            } elseif (!\is_string($value)) {
                $this->addError(
                    $this->buildPointer($pointer, $property),
                    \sprintf('The \'%s\' property should be a string', $property)
                );
                $isValid = false;
            } elseif ('' === \trim($value)) {
                $this->addError(
                    $this->buildPointer($pointer, $property),
                    \sprintf('The \'%s\' property should not be blank', $property)
                );
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * @param array  $data
     * @param string $property
     * @param string $pointer
     * @param bool   $notEmpty
     * @param bool   $associative
     *
     * @return bool
     */
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
                \sprintf('The \'%s\' property should be an array', $property)
            );
            $isValid = false;
        } elseif ($notEmpty && empty($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                \sprintf('The \'%s\' property should not be empty', $property)
            );
            $isValid = false;
        } elseif ($associative && !ArrayUtil::isAssoc($value)) {
            $this->addError(
                $this->buildPointer($pointer, $property),
                \sprintf('The \'%s\' property should be an associative array', $property)
            );
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param string $parentPath
     * @param string $property
     *
     * @return string
     */
    protected function buildPointer(string $parentPath, string $property): string
    {
        return $parentPath . '/' . $property;
    }

    /**
     * @param string $pointer
     * @param string $message
     */
    protected function addError(string $pointer, string $message): void
    {
        $this->addErrorObject(
            Error::createValidationError(Constraint::REQUEST_DATA, $message)
                ->setSource(ErrorSource::createByPointer($pointer))
        );
    }

    /**
     * @param Error $error
     */
    protected function addErrorObject(Error $error): void
    {
        $this->errors[] = $error;
    }
}
