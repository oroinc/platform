<?php

declare(strict_types=1);

namespace Oro\Bundle\CurrencyBundle\EventListener\ORM;

use Oro\Bundle\CurrencyBundle\DoctrineExtension\Dbal\Types\MoneyValueType;
use Oro\Bundle\EntityBundle\EventListener\ORM\FixDecimalChangeSetListener;
use Oro\DBAL\Types\MoneyType;

/**
 * This class aims to reduce Database updates on unchanged Money & MoneyValue field values
 */
class FixMoneyChangeSetListener extends FixDecimalChangeSetListener
{
    /**
     * @return string[]
     */
    protected function getSupportedTypes(): array
    {
        return [
            MoneyType::TYPE,
            MoneyValueType::TYPE,
        ];
    }
}
