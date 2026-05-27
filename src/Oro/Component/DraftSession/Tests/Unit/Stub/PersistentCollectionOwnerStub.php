<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Stub;

class PersistentCollectionOwnerStub
{
    public function __construct(private readonly ?object $owner)
    {
    }

    public function getOwner(): ?object
    {
        return $this->owner;
    }
}
