<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Stubs;

interface StubLayoutBuilderInterface
{
    public function add($id, $parentId, $blockType, array $options = [], $siblingId = null, $prepend = false);

    public function clear();
}
