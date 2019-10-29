<?php

namespace Oro\Bundle\SearchBundle\Exception;

/**
 * Engine related exception
 *
 * @deprecated since 3.1 will be removed after 4.1
 */
class UnsupportedStatisticInterfaceEngineException extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct("Engine doesn't support statistic aggregation functions.");
    }
}
