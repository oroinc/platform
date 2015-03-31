<?php

namespace Oro\Bundle\DashboardBundle\Provider;


interface ConfigValueConverter
{
    public function getConvertedValue($value);
}