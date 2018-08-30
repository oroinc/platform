<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Event;

use Oro\Bundle\TranslationBundle\Event\AfterCatalogueDump;
use Symfony\Component\Translation\MessageCatalogue;

class AfterCatalogueDumpTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCatalogue()
    {
        $catalogue = new MessageCatalogue('en');

        $event = new AfterCatalogueDump($catalogue);

        $this->assertSame($catalogue, $event->getCatalogue());
    }
}
