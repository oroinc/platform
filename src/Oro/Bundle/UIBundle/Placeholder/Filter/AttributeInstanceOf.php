<?php

namespace Oro\Bundle\UIBundle\Placeholder\Filter;

use Symfony\Component\DependencyInjection\ContainerInterface;

class AttributeInstanceOf implements PlaceholderFilterInterface
{
    const ATTRIBUTE_NAME = 'attribute_instance_of';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(array $items, array $variables)
    {
        $result = array();
        foreach ($items as $item) {
            if ($this->isAllowed($item, $variables)) {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Returns true only if item don't have "attribute_instance_of" or attribute exist and class instance matched.
     *
     * @param array $item
     * @param array $variables
     * @return bool
     */
    public function isAllowed(array $item, array $variables)
    {
        if (!isset($item[self::ATTRIBUTE_NAME])) {
            return true;
        }

        $attributeValue = array_values($item[self::ATTRIBUTE_NAME]);

        if (2 === count($attributeValue) && is_string($attributeValue[0]) && is_string($attributeValue[1])) {
            list($attributeName, $className) = $attributeValue;
            return $this->isAttributeInstanceOf($variables, $attributeName, $className);
        }

        return false;
    }

    /**
     * @param array $variables
     * @param string $attributeName
     * @param string $className
     * @return bool
     */
    protected function isAttributeInstanceOf(array $variables, $attributeName, $className)
    {
        if (!isset($variables[$attributeName])) {
            return false;
        }

        if (0 === strpos($className, '%')) {
            $className = $this->container->getParameter($className);
        }

        return $variables[$attributeName] instanceof $className;
    }
}
