<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Model;

use Oro\Bundle\DataAuditBundle\Model\MenuAuditObject;
use PHPUnit\Framework\TestCase;

class MenuAuditObjectTest extends TestCase
{
    private const string OBJECT_CLASS = 'Oro\Bundle\NavigationBundle\GlobalBackOfficeMenu';

    public function testSaysWhatItIsRecordedUnder(): void
    {
        $auditObject = new MenuAuditObject(self::OBJECT_CLASS, 'application_menu', 5, 'contact_us');

        self::assertSame(self::OBJECT_CLASS, $auditObject->getObjectClass());
        self::assertSame('5_application_menu_contact_us', $auditObject->getObjectId());
    }

    public function testTellsTheSameItemOfDifferentMenusAndOfDifferentTargetsApart(): void
    {
        $identifiers = [
            (new MenuAuditObject(self::OBJECT_CLASS, 'application_menu', 5, 'contact_us'))->getObjectId(),
            (new MenuAuditObject(self::OBJECT_CLASS, 'shortcuts_menu', 5, 'contact_us'))->getObjectId(),
            (new MenuAuditObject(self::OBJECT_CLASS, 'application_menu', 8, 'contact_us'))->getObjectId(),
        ];

        self::assertSame($identifiers, array_unique($identifiers));
    }

    public function testIsRecordedUnderAMenuThatIsCustomizedForNobodyInParticular(): void
    {
        $auditObject = new MenuAuditObject(self::OBJECT_CLASS, 'application_menu', null, 'contact_us');

        self::assertSame('0_application_menu_contact_us', $auditObject->getObjectId());
    }
}
