<?php

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node\Stub;

class SimpleObject
{
    /**
     * @var string
     */
    public $foo = 'baz';

    /**
     * @return string
     */
    public function foo()
    {
        return 'bar';
    }

    /**
     * @return array
     */
    public function values()
    {
        return [['index' => 11]];
    }
}
