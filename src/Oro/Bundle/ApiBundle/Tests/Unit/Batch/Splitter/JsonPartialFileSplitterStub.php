<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Splitter;

use Oro\Bundle\ApiBundle\Batch\Splitter\JsonPartialFileSplitter;

class JsonPartialFileSplitterStub extends JsonPartialFileSplitter
{
    /** @var int */
    private $sleepTimeout;

    public function __construct(int $sleepTimeout)
    {
        $this->sleepTimeout = $sleepTimeout * 1000;
    }

    /**
     * {@inheritdoc}
     */
    protected function saveChunk(): void
    {
        usleep($this->sleepTimeout);
        parent::saveChunk();
    }
}
