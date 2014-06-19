<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureRegistry;

class TemplateFixtureRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        $entity = 'stdClass';
        $fixture = $this->getMock('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface');
        $registry = new TemplateFixtureRegistry();

        $this->assertFalse($registry->hasEntityFixture($entity));
        $this->assertNull($registry->getEntityFixture($entity));

        $registry->addEntityFixture($entity, $fixture);
        $this->assertTrue($registry->hasEntityFixture($entity));
        $this->assertSame($fixture, $registry->getEntityFixture($entity));
    }
}
