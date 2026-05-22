<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Stub;

use Oro\Component\DraftSession\Entity\EntityDraftSoftDeleteAwareInterface;
use Oro\Component\DraftSession\Entity\EntityDraftSoftDeleteAwareTrait;

class EntityDraftSoftDeleteAwareStub extends EntityDraftAwareStub implements EntityDraftSoftDeleteAwareInterface
{
    use EntityDraftSoftDeleteAwareTrait;
}
