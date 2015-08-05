<?php

namespace Oro\Component\PhpUtils\Tests\Unit\Stubs;

interface StubInterface
{
    public function add($id, $parentId, $blockType, array $options = [], $siblingId = null, $prepend = false);

    public function clear();
}
