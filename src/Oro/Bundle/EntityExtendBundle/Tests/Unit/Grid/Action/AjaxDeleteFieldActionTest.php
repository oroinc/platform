<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid\Action;

use Oro\Bundle\EntityExtendBundle\Grid\Action\AjaxDeleteFieldAction;

class AjaxDeleteFieldActionTest extends \PHPUnit\Framework\TestCase
{
    public function testGetOptions()
    {
        $action = new AjaxDeleteFieldAction();
        $options = $action->getOptions()->toArray();

        $this->assertArrayHasKey('frontend_type', $options);
        $this->assertArrayHasKey('frontend_handle', $options);
        $this->assertEquals('ajaxdeletefield', $options['frontend_type']);
        $this->assertEquals('ajax', $options['frontend_handle']);
    }
}
