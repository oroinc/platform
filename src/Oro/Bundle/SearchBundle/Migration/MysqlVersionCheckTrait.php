<?php

namespace Oro\Bundle\SearchBundle\Migration;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MysqlVersionCheckTrait as BaseMysqlVersionCheckTrait;
use Psr\Container\ContainerInterface;

/**
 * Check MySQL Full Text compatibility compatible with ContainerAwareTrait
 *
 * @property ContainerInterface $container
 */
trait MysqlVersionCheckTrait
{
    use BaseMysqlVersionCheckTrait;

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }
}
