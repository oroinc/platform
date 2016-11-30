<?php

namespace Oro\Bundle\CurrencyBundle\Config;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyListAwareInterface;
use Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyAwareInterface;

interface CurrencyConfigInterface extends CurrencyListAwareInterface, DefaultCurrencyAwareInterface
{
}
