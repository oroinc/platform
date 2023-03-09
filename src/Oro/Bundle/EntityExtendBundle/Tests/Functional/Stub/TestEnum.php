<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Test enum class.
 */
class TestEnum extends AbstractEnumValue implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    public function removeName()
    {
        $this->setName('');
    }
}
