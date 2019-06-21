<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\PostHttpImportEvent;

class PostHttpImportEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $options = ['test1' => 'test2'];
        $event = new PostHttpImportEvent($options);

        $this->assertSame($options, $event->getOptions());
    }
}
