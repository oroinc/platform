<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Splitter;

use Oro\Bundle\ApiBundle\Batch\Splitter\JsonPartialFileSplitter;

class JsonPartialFileSplitterStub extends JsonPartialFileSplitter
{
    private int $sleepTimeout;

    public function __construct(int $sleepTimeout)
    {
        $this->sleepTimeout = $sleepTimeout * 1000;
    }

    #[\Override]
    protected function saveChunk(): void
    {
        usleep($this->sleepTimeout);
        parent::saveChunk();
    }
}
