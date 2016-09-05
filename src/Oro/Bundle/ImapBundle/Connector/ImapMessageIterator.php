<?php


namespace Oro\Bundle\ImapBundle\Connector;

use Oro\Bundle\ImapBundle\Mail\Storage\Imap;
use Oro\Bundle\ImapBundle\Mail\Storage\Message;

class ImapMessageIterator implements \Iterator, \Countable
{
    /** @var Imap */
    private $imap;

    /** @var int[]|null */
    private $ids;

    /** @var bool using message uids */
    private $uidMode;

    /** @var bool */
    private $reverse = false;

    /** @var int */
    private $batchSize = 1;

    /** @var \Closure|null */
    private $onBatchLoaded;

    /** @var Message[] an array is indexed by the Iterator keys */
    private $batch = [];

    /** @var int|null */
    private $iterationMin;

    /** @var int|null */
    private $iterationMax;

    /** @var int|null */
    private $iterationPos;

    /**
     * Constructor
     *
     * @param Imap       $imap
     * @param int[]|null $ids
     * @param bool       $uidMode
     */
    public function __construct(Imap $imap, array $ids = null, $uidMode = false)
    {
        $this->imap = $imap;
        $this->ids  = $ids;
        $this->uidMode  = $uidMode;
    }

    /**
     * Sets iteration order. To avoid extra requests to IMAP server
     * the rewind() method should be executed manually on demand.
     *
     * @param bool $reverse Determines the iteration order. By default from newest messages to oldest
     *                      true for from newest messages to oldest
     *                      false for from oldest messages to newest
     */
    public function setIterationOrder($reverse)
    {
        $this->reverse = $reverse;
    }

    /**
     * Sets batch size
     *
     * @param int $batchSize Determines how many messages can be loaded at once
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Sets a callback function is called when a batch is loaded
     *
     * @param \Closure|null $callback The callback function is called when a batch is loaded
     *                                function (Message[] $batch)
     */
    public function setBatchCallback(\Closure $callback = null)
    {
        $this->onBatchLoaded = $callback;
    }

    /**
     * The number of messages in this iterator
     *
     * @return int
     */
    public function count()
    {
        $this->ensureInitialized();

        return $this->ids === null
            ? $this->iterationMax
            : $this->iterationMax + 1;
    }

    /**
     * Return the current element
     *
     * @return Message
     */
    public function current()
    {
        return $this->batch[$this->iterationPos];
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        $this->increasePosition($this->iterationPos);

        if ($this->isValidPosition($this->iterationPos) && !array_key_exists($this->iterationPos, $this->batch)) {
            // initialize the batch
            $this->batch = [];
            if ($this->batchSize > 1) {
                $ids = [];
                $pos = $this->iterationPos;
                $i   = 0;
                while ($i < $this->batchSize && $this->isValidPosition($pos)) {
                    $ids[$pos] = $this->getMessageId($pos);
                    $this->increasePosition($pos);
                    $i++;
                }
                $messages = $this->imap->getMessages(array_values($ids));
                foreach ($ids as $pos => $id) {
                    $this->batch[$pos] = isset($messages[$id]) ? $messages[$id] : null;
                }
            } else {
                $this->batch[$this->iterationPos] = $this->imap->getMessage(
                    $this->getMessageId($this->iterationPos)
                );
            }
            if ($this->onBatchLoaded !== null) {
                call_user_func($this->onBatchLoaded, $this->batch);
            }
        }
    }

    /**
     * Return the key of the current element
     *
     * @return int on success, or null on failure.
     */
    public function key()
    {
        return $this->iterationPos;
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean Returns true on success or false on failure.
     */
    public function valid()
    {
        $this->ensureInitialized();

        return isset($this->batch[$this->iterationPos]);
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        $this->initialize();

        $this->iterationPos = $this->reverse
            ? ($this->iterationMax + 1)
            : ($this->iterationMin - 1);

        $this->batch = [];

        $this->next();
    }

    /**
     * Makes sure the Iterator is ready to work
     */
    protected function ensureInitialized()
    {
        if ($this->iterationMin === null || $this->iterationMax === null) {
            $this->initialize();
        }
    }

    /**
     * Prepares the Iterator to work
     */
    protected function initialize()
    {
        if ($this->ids === null) {
            $this->iterationMin = 1;
            $this->iterationMax = $this->imap->count();
        } else {
            $this->iterationMin = 0;
            $this->iterationMax = count($this->ids) - 1;
        }
    }

    /**
     * Get a message id by its position in the Iterator
     *
     * @param int $pos
     *
     * @return int
     */
    protected function getMessageId($pos)
    {
        return $this->ids === null
            ? $pos
            : $this->convertUidToId($this->ids[$pos]);
    }

    /**
     * Convert uid to id if using uid
     *
     * @param int $uid
     *
     * @return int
     */
    protected function convertUidToId($uid)
    {
        if ($this->uidMode) {
            return $this->imap->getNumberByUniqueId($uid);
        } else {
            return $uid;
        }
    }

    /**
     * Move the given position of the Iterator to the next element
     *
     * @param int $pos
     */
    protected function increasePosition(&$pos)
    {
        if ($this->reverse) {
            --$pos;
        } else {
            ++$pos;
        }
    }

    /**
     * Checks if the given position is valid
     *
     * @param int $pos
     *
     * @return boolean
     */
    protected function isValidPosition($pos)
    {
        return
            $pos !== null
            && $pos >= $this->iterationMin
            && $pos <= $this->iterationMax;
    }
}
