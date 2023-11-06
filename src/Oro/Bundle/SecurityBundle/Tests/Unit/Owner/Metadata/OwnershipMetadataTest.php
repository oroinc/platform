<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OwnershipMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructWithoutParameters(): void
    {
        $metadata = new OwnershipMetadata();
        self::assertEquals(OwnershipMetadata::OWNER_TYPE_NONE, $metadata->getOwnerType());
        self::assertFalse($metadata->hasOwner());
        self::assertFalse($metadata->isOrganizationOwned());
        self::assertFalse($metadata->isBusinessUnitOwned());
        self::assertFalse($metadata->isUserOwned());
        self::assertSame('', $metadata->getOrganizationFieldName());
        self::assertSame('', $metadata->getOrganizationColumnName());
        self::assertSame('', $metadata->getOwnerFieldName());
        self::assertSame('', $metadata->getOwnerColumnName());
    }

    public function testConstructWithNoneOwnership(): void
    {
        $metadata = new OwnershipMetadata('NONE');
        self::assertEquals(OwnershipMetadata::OWNER_TYPE_NONE, $metadata->getOwnerType());
        self::assertFalse($metadata->hasOwner());
        self::assertFalse($metadata->isOrganizationOwned());
        self::assertFalse($metadata->isBusinessUnitOwned());
        self::assertFalse($metadata->isUserOwned());
        self::assertSame('', $metadata->getOrganizationFieldName());
        self::assertSame('', $metadata->getOrganizationColumnName());
        self::assertSame('', $metadata->getOwnerFieldName());
        self::assertSame('', $metadata->getOwnerColumnName());
    }

    public function testConstructWithOrganizationOwnership(): void
    {
        $metadata = new OwnershipMetadata('ORGANIZATION', 'org', 'org_id');
        self::assertEquals(OwnershipMetadata::OWNER_TYPE_ORGANIZATION, $metadata->getOwnerType());
        self::assertTrue($metadata->hasOwner());
        self::assertTrue($metadata->isOrganizationOwned());
        self::assertFalse($metadata->isBusinessUnitOwned());
        self::assertFalse($metadata->isUserOwned());
        self::assertSame('org', $metadata->getOrganizationFieldName());
        self::assertSame('org_id', $metadata->getOrganizationColumnName());
        self::assertSame('org', $metadata->getOwnerFieldName());
        self::assertSame('org_id', $metadata->getOwnerColumnName());
    }

    public function testConstructWithBusinessUnitOwnership(): void
    {
        $metadata = new OwnershipMetadata('BUSINESS_UNIT', 'bu', 'bu_id', 'org', 'org_id');
        self::assertEquals(OwnershipMetadata::OWNER_TYPE_BUSINESS_UNIT, $metadata->getOwnerType());
        self::assertTrue($metadata->hasOwner());
        self::assertFalse($metadata->isOrganizationOwned());
        self::assertTrue($metadata->isBusinessUnitOwned());
        self::assertFalse($metadata->isUserOwned());
        self::assertSame('org', $metadata->getOrganizationFieldName());
        self::assertSame('org_id', $metadata->getOrganizationColumnName());
        self::assertSame('bu', $metadata->getOwnerFieldName());
        self::assertSame('bu_id', $metadata->getOwnerColumnName());
    }

    public function testConstructWithUserOwnership(): void
    {
        $metadata = new OwnershipMetadata('USER', 'usr', 'user_id', 'org', 'org_id');
        self::assertEquals(OwnershipMetadata::OWNER_TYPE_USER, $metadata->getOwnerType());
        self::assertTrue($metadata->hasOwner());
        self::assertFalse($metadata->isOrganizationOwned());
        self::assertFalse($metadata->isBusinessUnitOwned());
        self::assertTrue($metadata->isUserOwned());
        self::assertSame('org', $metadata->getOrganizationFieldName());
        self::assertSame('org_id', $metadata->getOrganizationColumnName());
        self::assertSame('usr', $metadata->getOwnerFieldName());
        self::assertSame('user_id', $metadata->getOwnerColumnName());
    }

    public function testConstructWithInvalidOwnerType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown owner type: test.');

        new OwnershipMetadata('test');
    }

    public function testConstructWithoutOwnerFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The owner field name must not be empty.');

        new OwnershipMetadata('ORGANIZATION');
    }

    public function testConstructWithoutOwnerIdColumnName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The owner column name must not be empty.');

        new OwnershipMetadata('ORGANIZATION', 'org');
    }

    public function testSerialization(): void
    {
        $metadata = new OwnershipMetadata(
            'USER',
            'owner',
            'owner_id',
            'org',
            'org_id'
        );

        $unserializedMetadata = unserialize(serialize($metadata));

        self::assertEquals($metadata, $unserializedMetadata);
        self::assertNotSame($metadata, $unserializedMetadata);
    }

    public function testSetState(): void
    {
        $metadata = new OwnershipMetadata('USER', 'org', 'org_id', 'owner', 'owner_id');

        $restoredMetadata = OwnershipMetadata::__set_state(
            [
                'ownerType' => $metadata->getOwnerType(),
                'organizationFieldName' => $metadata->getOrganizationFieldName(),
                'organizationColumnName' => $metadata->getOrganizationColumnName(),
                'ownerFieldName' => $metadata->getOwnerFieldName(),
                'ownerColumnName' => $metadata->getOwnerColumnName(),
                'not_exists' => true
            ]
        );

        self::assertEquals($metadata, $restoredMetadata);
        self::assertNotSame($metadata, $restoredMetadata);
    }

    /**
     * @dataProvider getAccessLevelNamesDataProvider
     */
    public function testGetAccessLevelNames(array $params, array $levels): void
    {
        [$ownerType, $ownerFieldName, $ownerColumnName] = $params;
        $metadata = new OwnershipMetadata($ownerType, $ownerFieldName, $ownerColumnName);

        self::assertEquals($levels, $metadata->getAccessLevelNames());
    }

    public function getAccessLevelNamesDataProvider(): array
    {
        return [
            'no owner' => [
                ['NONE', '', ''],
                [
                    AccessLevel::NONE_LEVEL => AccessLevel::NONE_LEVEL_NAME,
                    AccessLevel::SYSTEM_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
                ]
            ],
            'basic level owned' => [
                ['USER', 'owner', 'owner_id'],
                [
                    AccessLevel::NONE_LEVEL => AccessLevel::NONE_LEVEL_NAME,
                    AccessLevel::BASIC_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::BASIC_LEVEL),
                    AccessLevel::LOCAL_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::LOCAL_LEVEL),
                    AccessLevel::DEEP_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::DEEP_LEVEL),
                    AccessLevel::GLOBAL_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::GLOBAL_LEVEL)
                ]
            ],
            'local level owned' => [
                ['BUSINESS_UNIT', 'owner', 'owner_id'],
                [
                    AccessLevel::NONE_LEVEL => AccessLevel::NONE_LEVEL_NAME,
                    AccessLevel::LOCAL_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::LOCAL_LEVEL),
                    AccessLevel::DEEP_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::DEEP_LEVEL),
                    AccessLevel::GLOBAL_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::GLOBAL_LEVEL)
                ]
            ],
            'global level owned' => [
                ['ORGANIZATION', 'owner', 'owner_id'],
                [
                    AccessLevel::NONE_LEVEL => AccessLevel::NONE_LEVEL_NAME,
                    AccessLevel::GLOBAL_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::GLOBAL_LEVEL)
                ]
            ]
        ];
    }
}
