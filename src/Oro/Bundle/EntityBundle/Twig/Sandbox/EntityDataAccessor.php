<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface as ConfigProvider;

/**
 * Reads property values from entity objects.
 */
class EntityDataAccessor
{
    /** @var ConfigProvider */
    private $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * Attempts to get the value of the specified property.
     *
     * @param mixed  $entity   The source object, can be an object or an array
     * @param string $property The name of the property
     * @param mixed  $value    Contains a value of the specified property;
     *                         if the operation failed a value of this variable is unpredictable
     *
     * @return bool TRUE if a value is got; otherwise, FALSE
     */
    public function tryGetValue($entity, string $property, &$value): bool
    {
        $result = false;
        $entityClass = ClassUtils::getClass($entity);
        $config = $this->configProvider->getConfiguration();
        if (isset($config[ConfigProvider::ACCESSORS][$entityClass])) {
            $accessors = $config[ConfigProvider::ACCESSORS][$entityClass];
            if (\array_key_exists($property, $accessors)) {
                $accessor = $accessors[$property];
                try {
                    if ($accessor) {
                        // method
                        $value = $entity->{$accessor}();
                    } else {
                        // property
                        $value = $entity->{$property};
                    }
                    $result = true;
                } catch (\Throwable $e) {
                    // ignore any errors here
                }
            }
        }

        return $result;
    }
}
