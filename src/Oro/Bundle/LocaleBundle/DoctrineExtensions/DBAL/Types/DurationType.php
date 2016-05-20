<?php

namespace Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Types\IntegerType;

class DurationType extends IntegerType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'duration';
    }
}
