<?php
/**
 * Created by PhpStorm.
 * User: keeper
 * Date: 16.03.16
 * Time: 11:15
 */

namespace EntityPaginationBundle\Tests\Unit\Event;

use Oro\Bundle\EntityPaginationBundle\Event\StorageAliasesEvent;

class StorageAliasEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $aliases = ['test' => 'testAlias'];
        $event = new StorageAliasesEvent();
        $event->setAliases('test', 'testAlias');
        $this->assertSame($aliases, $event->getAliases());
    }
}