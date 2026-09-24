<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Model\AuditEntry;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use PHPUnit\Framework\TestCase;

class AuditEntryTest extends TestCase
{
    public function testDescribesWhatChanged(): void
    {
        $entry = new AuditEntry('Some\Virtual\Type', '42', 'Main Menu', Audit::ACTION_UPDATE);

        self::assertSame('Some\Virtual\Type', $entry->getObjectClass());
        self::assertSame('42', $entry->getObjectId());
        self::assertSame('Main Menu', $entry->getObjectName());
        self::assertSame(Audit::ACTION_UPDATE, $entry->getAction());
        self::assertFalse($entry->hasChanges());
        self::assertSame([], $entry->getChanges());
    }

    public function testAddChangeDefaultsToText(): void
    {
        $entry = new AuditEntry('Some\Virtual\Type', '42', 'Main Menu', Audit::ACTION_UPDATE);
        $entry->addChange('Contact Us', 'Contact Us', 'Contact');

        self::assertTrue($entry->hasChanges());
        self::assertSame(
            [
                'Contact Us' => [
                    'field' => 'Contact Us',
                    'type' => AuditFieldTypeRegistry::TYPE_TEXT,
                    'old' => 'Contact Us',
                    'new' => 'Contact',
                ],
            ],
            $entry->getChanges()
        );
    }

    public function testAddChangeKeepsTheGivenType(): void
    {
        $entry = new AuditEntry('Some\Virtual\Type', '0', 'Global', Audit::ACTION_UPDATE);
        $entry->addChange('oro_test.enabled', true, false, 'boolean');

        self::assertSame(
            ['field' => 'oro_test.enabled', 'type' => 'boolean', 'old' => true, 'new' => false],
            $entry->getChanges()['oro_test.enabled']
        );
    }

    public function testAddChangeKeepsTheLastChangeOfAField(): void
    {
        $entry = new AuditEntry('Some\Virtual\Type', '0', 'Global', Audit::ACTION_UPDATE);
        $entry->addChange('oro_test.foo', 'a', 'b');
        $entry->addChange('oro_test.foo', 'a', 'c');

        self::assertCount(1, $entry->getChanges());
        self::assertSame('c', $entry->getChanges()['oro_test.foo']['new']);
    }
}
