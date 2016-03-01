<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\EmptyFixture;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;

class EmptyFixtureTest extends \PHPUnit_Framework_TestCase
{
    public function testGetEntityClass()
    {
        $fixture = new EmptyFixture('\stdClass');
        $this->assertEquals('\stdClass', $fixture->getEntityClass());
    }

    public function testGetData()
    {
        $templateManager = new TemplateManager(new TemplateEntityRegistry());
        $templateManager->addEntityRepository(new EmptyFixture('\stdClass'));

        $fixture = $templateManager->getEntityFixture('\stdClass');
        $data    = $fixture->getData();
        $this->assertCount(1, $data);
        $this->assertInstanceOf('\stdClass', reset($data));
    }
}
