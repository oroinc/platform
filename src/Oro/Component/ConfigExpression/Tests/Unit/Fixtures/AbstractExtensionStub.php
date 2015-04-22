<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Fixtures;

use Oro\Component\ConfigExpression\Extension\AbstractExtension;

class AbstractExtensionStub extends AbstractExtension
{
    /** @var array */
    protected $loadedExpressions;

    /**
     * @param array $loadedExpressions
     */
    public function __construct($loadedExpressions)
    {
        $this->loadedExpressions = $loadedExpressions;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadExpressions()
    {
        return $this->loadedExpressions;
    }
}
