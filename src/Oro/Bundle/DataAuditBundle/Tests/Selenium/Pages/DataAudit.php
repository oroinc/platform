<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class DataAudit
 *
 * @package Oro\Bundle\DataAuditBundle\Tests\Selenium\Pages
 * @method DataAudit openDataAudit() openDataAudit(string $bundlePath)
 * @method assertTitle($title, $message = '')
 */
class DataAudit extends AbstractPageFilteredGrid
{
    const URL = 'audit';

    public function entityNew()
    {
        return $this;
    }

    public function entityView()
    {
        return $this;
    }
}
