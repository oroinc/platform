<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use ProxyManager\Proxy\LazyLoadingInterface;

abstract class LazyOroEntityManager extends OroEntityManager implements LazyLoadingInterface
{
}
