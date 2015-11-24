<?php

namespace Oro\Bundle\CronBundle\Entity\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use JMS\JobQueueBundle\Entity\Type\SafeObjectType as ParentType;

class SafeObjectType extends ParentType
{
    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
