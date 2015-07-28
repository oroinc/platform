<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Stub;

use Oro\Bundle\DataAuditBundle\Entity\AuditField as BaseAuditField;

class AuditField extends BaseAuditField
{
    protected $new_testingtype;
    protected $old_testingtype;
}
