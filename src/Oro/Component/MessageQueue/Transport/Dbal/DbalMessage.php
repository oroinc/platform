<?php

namespace Oro\Component\MessageQueue\Transport\Dbal;

use Oro\Component\MessageQueue\Transport\Message;

/**
 * A transport Message for DBAL connection.
 */
class DbalMessage extends Message implements DbalMessageInterface
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
