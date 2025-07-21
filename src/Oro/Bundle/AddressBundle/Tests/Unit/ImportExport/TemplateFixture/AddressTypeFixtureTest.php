<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\ImportExport\TemplateFixture;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\ImportExport\TemplateFixture\AddressTypeFixture;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use PHPUnit\Framework\TestCase;

class AddressTypeFixtureTest extends TestCase
{
    private AddressTypeFixture $fixture;

    #[\Override]
    protected function setUp(): void
    {
        $this->fixture = new AddressTypeFixture();
    }

    public function testGetEntityClass(): void
    {
        $this->assertEquals(AddressType::class, $this->fixture->getEntityClass());
    }

    public function testCreateEntity(): void
    {
        $entityRegistry = new TemplateEntityRegistry();
        $templateManager = new TemplateManager($entityRegistry);
        $this->fixture->setTemplateManager($templateManager);

        $type = AddressType::TYPE_BILLING;
        $this->assertEquals(new AddressType($type), $this->fixture->getEntity($type));
    }

    public function testFillEntityData(): void
    {
        $type = AddressType::TYPE_BILLING;
        $addressType = new AddressType($type);

        $this->assertEmpty($addressType->getLabel());
        $this->fixture->fillEntityData($type, $addressType);
        $this->assertEquals(ucfirst($type) . ' Type', $addressType->getLabel());
    }
}
