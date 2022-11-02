<?php

namespace Oro\Component\MessageQueue\Transport\Dbal;

use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * A transport Message for DBAL connection.
 */
interface DbalMessageInterface extends MessageInterface
{
    public function getId(): ?int;

    public function setId(int $id): void;
}
