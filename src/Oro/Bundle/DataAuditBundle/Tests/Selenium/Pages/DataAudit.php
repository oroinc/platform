<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class DataAudit
 *
 * @package Oro\Bundle\DataAuditBundle\Tests\Selenium\Pages
 * @method \Oro\Bundle\DataAuditBundle\Tests\Selenium\Pages\DataAudit openDataAudit() openDataAudit()
 * @method \Oro\Bundle\DataAuditBundle\Tests\Selenium\Pages\DataAudit assertTitle() assertTitle($title, $message = '')
 */
class DataAudit extends AbstractPageFilteredGrid
{
    const URL = 'audit';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);

    }

    public function open($entityData = array())
    {
        return;
    }
}
