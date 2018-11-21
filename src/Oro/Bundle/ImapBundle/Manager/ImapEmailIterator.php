<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Oro\Bundle\ImapBundle\Connector\ImapMessageIterator;
use Oro\Bundle\ImapBundle\Mail\Storage\Message;
use Oro\Bundle\ImapBundle\Manager\DTO\Email;
use Psr\Log\LoggerInterface;

/**
 * Iterator for Imap emails.
 */
class ImapEmailIterator implements \Iterator, \Countable
{
    /** @var ImapMessageIterator */
    private $iterator;

    /** @var ImapEmailManager */
    private $manager;

    /** @var Email[]|null an array is indexed by underlying iterator keys */
    private $batch;

    /** @var \Closure */
    private $onBatchLoaded;

    /** @var \Closure */
    private $onConvertError;

    /** @var int|null */
    private $iterationPos = 0;

    /**
     * Constructor
     *
     * @param ImapMessageIterator $iterator
     * @param ImapEmailManager    $manager
     */
    public function __construct(ImapMessageIterator $iterator, ImapEmailManager $manager)
    {
        $this->iterator = $iterator;
        $this->manager  = $manager;

        $this->onBatchLoaded = function ($batch) {
            $this->handleBatchLoaded($batch);
        };
        $this->setBatchCallback();
    }

    /**
     * Sets iteration order
     *
     * @param bool $reverse Determines the iteration order. By default from newest emails to oldest
     *                      true for from newest emails to oldest
     *                      false for from oldest emails to newest
     */
    public function setIterationOrder($reverse)
    {
        $this->iterator->setIterationOrder($reverse);
    }

    /**
     * Sets batch size
     *
     * @param int $batchSize Determines how many messages can be loaded at once
     */
    public function setBatchSize($batchSize)
    {
        $this->iterator->setBatchSize($batchSize);
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->iterator->setLogger($logger);
    }

    /**
     * Sets a callback function is called when a batch is loaded
     *
     * @param \Closure|null $callback The callback function is called when a batch is loaded
     *                                function (Email[] $batch)
     */
    public function setBatchCallback(\Closure $callback = null)
    {
        if ($callback === null) {
            // restore default callback
            $this->iterator->setBatchCallback($this->onBatchLoaded);
        } else {
            $iteratorCallback = function ($batch) use ($callback) {
                call_user_func($this->onBatchLoaded, $batch);
                call_user_func($callback, $this->batch);
            };
            $this->iterator->setBatchCallback($iteratorCallback);
        }
    }

    /**
     * Sets a callback function that will handle message convert error. If this callback set then iterator will work
     * in fail safe mode invalid messages will just skipped
     *
     * @param callable $callback The callback function.
     *                           function (\Exception)
     */
    public function setConvertErrorCallback(\Closure $callback = null)
    {
        $this->onConvertError = $callback;
        $this->iterator->setConvertErrorCallback($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->iterator->count();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->batch[$this->iterationPos];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->iterationPos++;

        // call the underlying iterator to make sure a batch is loaded
        // actually $this->batch is initialized at this moment
        $this->iterator->next();

        // skip invalid messages (they are not added to $this->batch)
        while (!isset($this->batch[$this->iterationPos]) && $this->iterator->valid()) {
            $this->iterationPos++;
            $this->iterator->next();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iterationPos;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->batch[$this->iterationPos]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->iterationPos = 0;
        $this->batch        = [];

        $this->iterator->rewind();
    }

    /**
     * @param Message[] $batch
     *
     * @throws \Exception
     */
    protected function handleBatchLoaded($batch)
    {
        $this->batch = [];

        $counter = 0;
        foreach ($batch as $key => $val) {
            try {
                $email = $this->manager->convertToEmail($val);
            } catch (\Exception $e) {
                if (null !== $this->onConvertError) {
                    call_user_func($this->onConvertError, $e);
                    $email = null;
                } else {
                    throw $e;
                }
            }

            // do not add invalid messages to $this->batch
            if ($email !== null) {
                $this->batch[$this->iterationPos + $counter] = $email;
            }
            $counter++;
        }
    }
}
