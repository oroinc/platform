<?php

namespace Oro\Component\MessageQueue\Transport\Dbal;

use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * A transport Message for DBAL connection.
 */
interface DbalMessageInterface extends MessageInterface
{
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @param int $id
     *
     * @return void
     */
    public function setId(int $id): void;
}
