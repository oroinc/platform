<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Extension\ActionMaskBuilder;
use PHPUnit\Framework\TestCase;

class ActionMaskBuilderTest extends TestCase
{
    public function testAllGroup(): void
    {
        $this->assertEquals(
            ActionMaskBuilder::GROUP_ALL,
            ActionMaskBuilder::MASK_EXECUTE
        );
    }

    public function testRemoveServiceBits(): void
    {
        $this->assertEquals(
            ActionMaskBuilder::REMOVE_SERVICE_BITS,
            ActionMaskBuilder::GROUP_ALL
        );
    }

    public function testServiceBits(): void
    {
        $this->assertEquals(
            ActionMaskBuilder::SERVICE_BITS,
            ~ActionMaskBuilder::REMOVE_SERVICE_BITS
        );
    }

    public function testGetEmptyPattern(): void
    {
        $builder = new ActionMaskBuilder();

        $this->assertEquals(
            '(E) .',
            $builder->getPattern()
        );
    }

    public function testGetPatternExecute(): void
    {
        $builder = new ActionMaskBuilder();
        $builder->add(ActionMaskBuilder::MASK_EXECUTE);

        $this->assertEquals(
            '(E) E',
            $builder->getPattern()
        );
    }
}
