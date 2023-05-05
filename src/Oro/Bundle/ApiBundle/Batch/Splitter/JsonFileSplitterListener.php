<?php

namespace Oro\Bundle\ApiBundle\Batch\Splitter;

use JsonStreamingParser\Exception\ParsingException;
use JsonStreamingParser\Listener\ListenerInterface;

/**
 * The JsonStreamingParser listener that constructs an in-memory representation of the JSON document.
 */
class JsonFileSplitterListener implements ListenerInterface
{
    /** @var array */
    protected $stack;

    /** @var string */
    protected $key;

    /** @var int */
    protected $level;

    /** @var int */
    protected $objectLevel;

    /** @var array */
    protected $objectKeys;

    /** @var callable|null */
    protected $sectionCallback;

    /** @var callable|null */
    protected $objectCallback;

    /** @var string|null */
    protected $headerSectionName;

    /** @var callable|null */
    protected $headerCallback;

    /** @var string[] */
    protected $sectionNamesToSplit;

    /**
     * @param callable|null $sectionCallback     The callback for parsed first level key item
     * @param callable|null $objectCallback      The callback for parsed collection item
     * @param string|null   $headerSectionName   The name of a header section
     * @param callable|null $headerCallback      The callback for parsed header section
     * @param string[]      $sectionNamesToSplit The names of sections to be split
     */
    public function __construct(
        callable $sectionCallback = null,
        callable $objectCallback = null,
        string $headerSectionName = null,
        callable $headerCallback = null,
        array $sectionNamesToSplit = []
    ) {
        $this->sectionCallback = $sectionCallback;
        $this->objectCallback = $objectCallback;
        $this->headerSectionName = $headerSectionName;
        $this->headerCallback = $headerCallback;
        $this->sectionNamesToSplit = $sectionNamesToSplit;
    }

    /**
     * {@inheritdoc}
     */
    public function startDocument(): void
    {
        $this->stack = [];
        $this->key = null;
        $this->level = 0;
        $this->objectLevel = 0;
        $this->objectKeys = [];
    }

    /**
     * {@inheritdoc}
     */
    public function endDocument(): void
    {
        // nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function startObject(): void
    {
        $this->objectLevel++;
        $this->startCommon();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function endObject(): void
    {
        $this->endCommon();
        $this->objectLevel--;
        if (1 !== $this->objectLevel) {
            return;
        }

        $sectionName = $this->objectKeys[$this->objectLevel + 1] ?? null;
        if (null !== $this->headerSectionName && $sectionName === $this->headerSectionName) {
            if (\count($this->objectKeys) !== 2
                || null !== $this->objectKeys[1]
                || $this->headerSectionName !== $this->objectKeys[2]
            ) {
                throw new ParsingException(
                    0,
                    0,
                    sprintf(
                        'The object with the key "%s" should be the first object in the document.',
                        $this->headerSectionName
                    )
                );
            }
            $header = $this->stack[0];
            $this->stack[0] = [];
            if (\is_callable($this->headerCallback)) {
                \call_user_func($this->headerCallback, $header);
            }
        } elseif (!$this->sectionNamesToSplit || \in_array($sectionName, $this->sectionNamesToSplit, true)) {
            if (!$this->stack[1]) {
                throw new ParsingException(
                    0,
                    0,
                    'Document must start with a named object that has an array as a value.'
                );
            }
            while ($this->stack[1]) {
                $obj = array_shift($this->stack[1]);
                if (\is_callable($this->objectCallback)) {
                    \call_user_func($this->objectCallback, $obj);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function startArray(): void
    {
        $this->startCommon();
    }

    /**
     * {@inheritdoc}
     */
    public function endArray(): void
    {
        $this->endCommon();
    }

    /**
     * {@inheritdoc}
     */
    public function key(string $key): void
    {
        $this->key = $key;
        if ($this->objectLevel === 1 && \is_callable($this->sectionCallback)) {
            \call_user_func($this->sectionCallback, $key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function value($value)
    {
        // Retrieve the item that we're currently parsing from the stack.
        $obj = array_pop($this->stack);
        if ($this->key) {
            $obj[$this->key] = $value;
            $this->key = null;
        } else {
            $obj[] = $value;
        }
        // Add the current item back into the stack.
        $this->stack[] = $obj;
    }

    public function whitespace(string $whitespace): void
    {
        // nothing to do here
    }

    protected function startCommon(): void
    {
        $this->level++;
        $this->objectKeys[$this->level] = $this->key ?: null;
        $this->key = null;

        $this->stack[] = [];
    }

    protected function endCommon(): void
    {
        $obj = array_pop($this->stack);
        if (!empty($this->stack)) {
            $parentObj = array_pop($this->stack);
            if ($this->objectKeys[$this->level]) {
                $parentObj[$this->objectKeys[$this->level]] = $obj;
            } else {
                $parentObj[] = $obj;
            }
            $this->stack[] = $parentObj;
        }
        $this->level--;
    }
}
