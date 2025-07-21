<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Entity;

use Oro\Bundle\MigrationBundle\Entity\DataFixture;
use PHPUnit\Framework\TestCase;

class DataFixtureTest extends TestCase
{
    private DataFixture $dataFixtureEntity;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataFixtureEntity = new DataFixture();
    }

    public function testDataFixtureEntity(): void
    {
        $this->assertNull($this->dataFixtureEntity->getId());
        $this->assertNull($this->dataFixtureEntity->getClassName());
        $this->dataFixtureEntity->setClassName('testClass');
        $this->assertEquals('testClass', $this->dataFixtureEntity->getClassName());
        $this->assertNull($this->dataFixtureEntity->getLoadedAt());
        $this->dataFixtureEntity->setLoadedAt(new \DateTime('2013-01-01'));
        $this->assertEquals('2013-01-01', $this->dataFixtureEntity->getLoadedAt()->format('Y-m-d'));
    }
}
