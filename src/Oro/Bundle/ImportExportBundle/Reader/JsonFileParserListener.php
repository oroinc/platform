<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use JsonStreamingParser\Exception\ParsingException;
use JsonStreamingParser\Listener\ListenerInterface;
use JsonStreamingParser\Listener\ParserAwareInterface;
use JsonStreamingParser\Listener\PositionAwareInterface;
use JsonStreamingParser\Parser;

/**
 * The listener for {@see JsonFileParser} that allows to parse root items one by one.
 */
class JsonFileParserListener implements ListenerInterface, ParserAwareInterface, PositionAwareInterface
{
    private array $stack = [];
    private ?string $key = null;
    private int $level = 0;
    private int $objectLevel = 0;
    private array $objectKeys = [];
    private bool $eof = false;
    private JsonFileParser $parser;
    private int $lineNumber = 1;
    private int $charNumber = 1;

    #[\Override]
    public function setParser(Parser $parser): void
    {
        if (!$parser instanceof JsonFileParser) {
            throw new \LogicException(\sprintf(
                'The parser must be an instance of "%s", got "%s".',
                JsonFileParser::class,
                \get_class($parser)
            ));
        }
        $this->parser = $parser;
    }

    #[\Override]
    public function setFilePosition(int $lineNumber, int $charNumber): void
    {
        $this->lineNumber = $lineNumber;
        $this->charNumber = $charNumber;
    }

    #[\Override]
    public function startDocument(): void
    {
        $this->stack = [];
        $this->key = null;
        $this->level = 0;
        $this->objectLevel = 0;
        $this->objectKeys = [];
    }

    #[\Override]
    public function endDocument(): void
    {
        $this->eof = true;
    }

    #[\Override]
    public function startObject(): void
    {
        if (empty($this->stack)) {
            throw new ParsingException($this->lineNumber, $this->charNumber, 'Document must start with array.');
        }

        if (0 === $this->objectLevel) {
            $this->stack[0] = [];
        }
        $this->objectLevel++;
        $this->startCommon();
    }

    #[\Override]
    public function endObject(): void
    {
        $this->endCommon();
        $this->objectLevel--;
        if (0 === $this->objectLevel) {
            $this->parser->break();
        }
    }

    #[\Override]
    public function startArray(): void
    {
        $this->startCommon();
    }

    #[\Override]
    public function endArray(): void
    {
        $this->endCommon();
    }

    #[\Override]
    public function key(string $key): void
    {
        $this->key = $key;
    }

    #[\Override]
    public function value(mixed $value): void
    {
        // Retrieve the item that we're currently parsing from the stack.
        $obj = array_pop($this->stack);
        if ($this->key) {
            $obj[$this->key] = $value;
            $this->key = null;
        } elseif (0 === $this->objectLevel) {
            throw new ParsingException($this->lineNumber, $this->charNumber - 3, 'The object cannot bu null.');
        } else {
            $obj[] = $value;
        }
        // Add the current item back into the stack.
        $this->stack[] = $obj;
    }

    #[\Override]
    public function whitespace(string $whitespace): void
    {
        // nothing to do here
    }

    public function reset(): void
    {
        $this->stack = [];
        $this->key = null;
        $this->level = 0;
        $this->objectLevel = 0;
        $this->objectKeys = [];
        $this->eof = false;
    }

    public function isEof(): bool
    {
        return $this->eof;
    }

    public function hasItem(): bool
    {
        return !empty($this->stack[0]);
    }

    public function getItem(): mixed
    {
        if (empty($this->stack[0])) {
            throw new \LogicException('There is no item.');
        }

        return reset($this->stack[0]);
    }

    private function startCommon(): void
    {
        $this->level++;
        $this->objectKeys[$this->level] = $this->key ?: null;
        $this->key = null;
        $this->stack[] = [];
    }

    private function endCommon(): void
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
