<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use JsonStreamingParser\Listener\PositionAwareInterface;
use JsonStreamingParser\Parser;

/**
 * JSON streaming parser that allows to parse root items one by one.
 * Unfortunately it is not possible to implement this class without the reflection
 * because the base parser protects its state with "private" keyword.
 */
class JsonFileParser extends Parser
{
    private \Closure $privatePropertyAccessor;
    private \Closure $consumeCharAccessor;
    private ?string $currentLine = null;
    private int $currentLinePos = 0;
    private bool $breakParsing = false;

    /**
     * @param resource $stream
     */
    public function __construct($stream)
    {
        parent::__construct($stream, new JsonFileParserListener());

        $this->privatePropertyAccessor = \Closure::bind(
            function & ($name) {
                if (!property_exists($this, $name)) {
                    throw new \ReflectionException(\sprintf('The property "%s" does not exist.', $name));
                }

                return $this->{$name};
            },
            $this,
            Parser::class
        );
        $this->consumeCharAccessor = \Closure::bind(
            function ($parser, $c) {
                $parser->consumeChar($c);
            },
            null,
            Parser::class
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function parse(): void
    {
        $privatePropertyAccessor = $this->privatePropertyAccessor;
        $lineNumber = &$privatePropertyAccessor('lineNumber');
        $charNumber = &$privatePropertyAccessor('charNumber');
        $stream = $privatePropertyAccessor('stream');
        $bufferSize = $privatePropertyAccessor('bufferSize');
        $lineEnding = $privatePropertyAccessor('lineEnding');
        $listener = $privatePropertyAccessor('listener');

        if (null === $lineNumber) {
            $lineNumber = 1;
        }
        if (null === $charNumber) {
            $charNumber = 1;
        }

        $this->breakParsing = false;
        $eof = false;
        while (null !== $this->currentLine || (!feof($stream) && !$eof)) {
            $line = $this->currentLine;
            $ended = false;
            if (null === $line) {
                $this->currentLine = null;
                $this->currentLinePos = 0;

                $startPos = ftell($stream);
                // set the underlying streams chunk size, so it delivers according to the request from stream_get_line
                stream_set_chunk_size($stream, $bufferSize);
                $line = stream_get_line($stream, $bufferSize, $lineEnding);
                if (false === $line) {
                    $line = '';
                }
                $endPos = ftell($stream);
                $ended = (bool)($endPos - \strlen($line) - $startPos);
                // if we're still at the same place after stream_get_line, we're done
                $eof = $endPos === $startPos;
            }

            $byteLen = \strlen($line);
            for ($i = $this->currentLinePos; $i < $byteLen; $i++) {
                if ($listener instanceof PositionAwareInterface) {
                    $listener->setFilePosition($lineNumber, $charNumber);
                }
                \call_user_func($this->consumeCharAccessor, $this, $line[$i]);
                $charNumber++;

                if ($this->breakParsing) {
                    $this->currentLine = $line;
                    $this->currentLinePos = $i + 1;

                    return;
                }
            }

            if ($ended) {
                $lineNumber++;
                $charNumber = 1;
            }

            $this->currentLine = null;
            $this->currentLinePos = 0;
        }
    }

    public function break(): void
    {
        $this->breakParsing = true;
    }

    public function reset(): void
    {
        $this->currentLine = null;
        $this->currentLinePos = 0;
        $this->getListener()->reset();
    }

    public function hasItem(): bool
    {
        return $this->getListener()->hasItem();
    }

    public function getItem(): mixed
    {
        return $this->getListener()->getItem();
    }

    public function isEof(): bool
    {
        return $this->getListener()->isEof();
    }

    private function getListener(): JsonFileParserListener
    {
        $privatePropertyAccessor = $this->privatePropertyAccessor;

        return $privatePropertyAccessor('listener');
    }
}
