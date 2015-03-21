<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

interface StubLayoutBuilderInterface
{
    public function add($id, $parentId, $blockType, array $options = [], $siblingId = null, $prepend = false);

    public function clear();
}
