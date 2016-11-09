<?php

namespace Oro\Component\Testing\Unit\Entity\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class StubEnumValue extends AbstractEnumValue
{
    /**
     * @param string $id
     * @param string $name
     * @param int    $priority
     * @param bool   $default
     */
    public function __construct($id, $name, $priority = 0, $default = false)
    {
        parent::__construct($id, $name, $priority, $default);
    }
}
