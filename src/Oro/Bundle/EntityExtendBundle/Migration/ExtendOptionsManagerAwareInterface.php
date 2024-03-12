<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

/**
 * This interface should be implemented by migrations that depend on {@see ExtendOptionsManager}.
 */
interface ExtendOptionsManagerAwareInterface
{
    public function setExtendOptionsManager(ExtendOptionsManager $extendOptionsManager): void;
}
