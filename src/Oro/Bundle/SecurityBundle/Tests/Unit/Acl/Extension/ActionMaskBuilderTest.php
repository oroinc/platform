<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Extension\ActionMaskBuilder;

class ActionMaskBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testAllGroup()
    {
        $this->assertEquals(
            ActionMaskBuilder::GROUP_ALL,
            ActionMaskBuilder::MASK_EXECUTE
        );
    }

    public function testRemoveServiceBits()
    {
        $this->assertEquals(
            ActionMaskBuilder::REMOVE_SERVICE_BITS,
            ActionMaskBuilder::GROUP_ALL
        );
    }

    public function testServiceBits()
    {
        $this->assertEquals(
            ActionMaskBuilder::SERVICE_BITS,
            ~ActionMaskBuilder::REMOVE_SERVICE_BITS
        );
    }

    public function testGetEmptyPattern()
    {
        $builder = new ActionMaskBuilder();

        $this->assertEquals(
            '(E) .',
            $builder->getPattern()
        );
    }

    public function testGetPatternExecute()
    {
        $builder = new ActionMaskBuilder();
        $builder->add(ActionMaskBuilder::MASK_EXECUTE);

        $this->assertEquals(
            '(E) E',
            $builder->getPattern()
        );
    }
}
