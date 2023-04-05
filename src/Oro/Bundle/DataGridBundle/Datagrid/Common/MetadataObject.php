<?php

namespace Oro\Bundle\DataGridBundle\Datagrid\Common;

use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Component\Config\Common\ConfigObject;

/**
 * This class represents metadata with datagrid configuration.
 */
class MetadataObject extends ConfigObject
{
    const GRID_NAME_KEY        = 'gridName';
    const OPTIONS_KEY          = 'options';
    const REQUIRED_MODULES_KEY = 'jsmodules';
    const LAZY_KEY             = 'lazy';

    /**
     * Default metadata array
     *
     * @return array
     */
    protected static function getDefaultMetadata()
    {
        return [
            self::REQUIRED_MODULES_KEY => [],
            self::OPTIONS_KEY          => [],
            self::LAZY_KEY             => true,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function createNamed($name, array $params)
    {
        $params = array_merge(self::getDefaultMetadata(), $params);
        $params[self::OPTIONS_KEY][self::GRID_NAME_KEY] = $name;
        $config = self::create($params);
        $config->setPropertyAccessor(PropertyAccess::createPropertyAccessorWithDotSyntax());

        return $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        if (!isset($this[self::OPTIONS_KEY][self::GRID_NAME_KEY])) {
            throw new LogicException("Trying to get name of unnamed object");
        }

        return $this[self::OPTIONS_KEY][self::GRID_NAME_KEY];
    }
}
