<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\EntitySerializer\DataAccessorInterface;

class EntityPropertyAccessor implements ObjectPropertyAccessorInterface
{
    /** @var DataAccessorInterface */
    protected $dataAccessor;

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
    public function getValue($object, $propertyName)
    {
        return $this->dataAccessor->getValue($object, $propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($object, $propertyName)
    {
        return $this->dataAccessor->hasGetter(ClassUtils::getClass($object), $propertyName);
    }
}
