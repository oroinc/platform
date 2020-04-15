<?php

namespace Oro\Bundle\ApiBundle\Batch\Splitter;

use JsonStreamingParser\Listener\ListenerInterface;
use JsonStreamingParser\Parser;
use Oro\Bundle\ApiBundle\Exception\TimeoutExceededFileSplitterException;
use Oro\Bundle\GaufretteBundle\FileManager;

/**
 * Splits a JSON file to chunks with possibility to limit the splitting time.
 */
class JsonPartialFileSplitter extends JsonFileSplitter implements PartialFileSplitterInterface
{
    /** @var bool */
    private $completed = false;

    /** @var int */
    private $timeout = -1;

    /** @var array */
    private $state = [];

    /** @var float */
    private $chunkStartTime;

    /** @var int */
    private $chunkTime = 0;

    /** @var int */
    private $chunkCount = 0;

    /**
     * {@inheritdoc}
     */
    public function isCompleted(): bool
    {
        return $this->completed;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeout(int $milliseconds): void
    {
        $this->timeout = $milliseconds;
    }

    /**
     * {@inheritdoc}
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function setState(array $data): void
    {
        $this->state = $data;
        $this->headerSectionData = $this->state['headerSection'] ?? null;
        $this->targetFileIndex = $this->state['targetFileIndex'] ?? 0;
        $this->targetFileFirstRecordOffset = $this->state['targetFileFirstRecordOffset'] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function splitFile(string $fileName, FileManager $srcFileManager, FileManager $destFileManager): array
    {
        try {
            return parent::splitFile($fileName, $srcFileManager, $destFileManager);
        } finally {
            $this->headerSectionData = $this->state['headerSection'] ?? null;
            $this->targetFileIndex = $this->state['targetFileIndex'] ?? 0;
            $this->targetFileFirstRecordOffset = $this->state['targetFileFirstRecordOffset'] ?? 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function parse(Parser $parser): void
    {
        /** @var JsonPartialFileParser $parser */

        $this->completed = true;
        $parser->setState($this->state);
        if (array_key_exists('sectionName', $this->state)) {
            $this->sectionName = $this->state['sectionName'];
        }
        try {
            parent::parse($parser);
        } catch (TimeoutExceededFileSplitterException $e) {
            $this->completed = false;
        } finally {
            $this->state = $parser->getState();
            $this->state['sectionName'] = $this->sectionName;
            if (null !== $this->headerSectionData) {
                $this->state['headerSection'] = $this->headerSectionData;
            }
            $this->state['targetFileIndex'] = $this->targetFileIndex;
            $this->state['targetFileFirstRecordOffset'] = $this->targetFileFirstRecordOffset;
            $this->chunkStartTime = null;
            $this->chunkTime = 0;
            $this->chunkCount = 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getParser($stream): Parser
    {
        return new JsonPartialFileParser($stream, $this->getParserListener());
    }

    /**
     * {@inheritdoc}
     */
    protected function getParserListener(): ListenerInterface
    {
        return new JsonPartialFileSplitterListener(
            function ($item) {
                $this->processSection($item);
            },
            function ($item) {
                $this->processItem($item);
            },
            $this->getHeaderSectionName(),
            function (array $header) {
                $this->processHeader($header);
            },
            $this->getSectionNamesToSplit()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function processItem($item): void
    {
        if (-1 !== $this->timeout && null === $this->chunkStartTime) {
            $this->chunkStartTime = microtime(true);
        }

        parent::processItem($item);
    }

    /**
     * {@inheritdoc}
     */
    protected function saveChunk(): void
    {
        parent::saveChunk();

        if (-1 !== $this->timeout) {
            $this->chunkCount++;
            $this->chunkTime += (int)round(1000 * (microtime(true) - $this->chunkStartTime));
            $this->chunkStartTime = null;

            if ($this->chunkTime >= $this->timeout
                || ((int)round($this->chunkTime / $this->chunkCount)) > $this->timeout - $this->chunkTime
            ) {
                throw new TimeoutExceededFileSplitterException();
            }
        }
    }
}
