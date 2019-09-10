<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

use Doctrine\Common\Util\ClassUtils;
use Oro\Component\EntitySerializer\DataAccessorInterface;

/**
 * Provides an access to properties of manageable entities.
 */
class EntityPropertyAccessor implements ObjectPropertyAccessorInterface
{
    /** @var DataAccessorInterface */
    private $dataAccessor;

    /**
     * @param DataAccessorInterface $dataAccessor
     */
    public function __construct(DataAccessorInterface $dataAccessor)
    {
        $this->dataAccessor = $dataAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object, string $propertyName)
    {
        return $this->dataAccessor->getValue($object, $propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($object, string $propertyName): bool
    {
        return $this->dataAccessor->hasGetter(ClassUtils::getClass($object), $propertyName);
    }
}
