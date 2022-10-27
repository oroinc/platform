<?php

namespace Oro\Bundle\ApiBundle\Util;

use Symfony\Component\PropertyAccess\Exception as PropertyAccessorException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Reads and writes values from/to a request data array by a property path.
 */
class RequestDataAccessor
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param array  $requestData
     * @param string $path
     *
     * @return mixed
     *
     * @throws PropertyAccessorException\AccessException          If an index does not exist
     * @throws PropertyAccessorException\UnexpectedTypeException  If a value within the path is not array
     */
    public function getValue(array $requestData, string $path)
    {
        return $this->getPropertyAccessor()->getValue($requestData, self::getPropertyPath($path));
    }

    /**
     * @param array  $requestData
     * @param string $path
     * @param mixed  $value
     *
     * @throws PropertyAccessorException\UnexpectedTypeException  If a value within the path is not array
     */
    public function setValue(array &$requestData, string $path, $value): void
    {
        $this->getPropertyAccessor()->setValue($requestData, self::getPropertyPath($path), $value);
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->enableExceptionOnInvalidIndex()
                ->getPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    private static function getPropertyPath(string $path): string
    {
        return '[' . str_replace(ConfigUtil::PATH_DELIMITER, '][', $path) . ']';
    }
}
