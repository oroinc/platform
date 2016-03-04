<?php

namespace Oro\Bundle\ActionBundle\Condition;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Component\ConfigExpression\Condition\AbstractComparison;
use Oro\Component\ConfigExpression\Exception;

class CollectionElementValueExists extends AbstractComparison
{
    const NAME = 'collection_element_value_exists';

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (count($options) < 2) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 2 or more elements, but %d given.', count($options))
            );
        }

        $this->left = [];

        for ($i = 0; $i < count($options); $i++) {
            $option = array_shift($options);

            if (!$option instanceof PropertyPathInterface) {
                throw new Exception\InvalidArgumentException(
                    sprintf('Option with index %s must be property path.', $i)
                );
            }

            $this->left[] = $option;
        }

        $this->right = array_shift($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompare($collection, $needle)
    {
        return in_array($needle, $collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveValue($context, $value, $strict = true)
    {
        if (!is_array($value)) {
            return parent::resolveValue($context, $value, $strict);
        }

        return $this->resolvePath($context, array_shift($value), $value);
    }

    /**
     * @param mixed $context
     * @param PropertyPathInterface $propertyPath
     * @param array $propertyPaths
     * @return array|mixed
     */
    protected function resolvePath($context, PropertyPathInterface $propertyPath, array $propertyPaths)
    {
        $data = $this->resolveValue($context, $propertyPath);

        if ((is_array($data) || $data instanceof Collection) && count($propertyPaths)) {
            $propertyPath = array_shift($propertyPaths);
            $result = [];

            foreach ($data as $item) {
                $result[] = $this->resolvePath(new ActionData(['data' => $item]), $propertyPath, $propertyPaths);
            }

            return $result;
        }

        return $data;
    }
}
