<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Collection;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\ApiAction;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IncludedEntityDataTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldBeMarkedAsNewByDefault(): void
    {
        $data = new IncludedEntityData('path', 0);
        self::assertFalse($data->isExisting());
    }

    public function testShouldUseCreateTargetActionByDefault(): void
    {
        $data = new IncludedEntityData('path', 0);
        self::assertEquals(ApiAction::CREATE, $data->getTargetAction());
    }

    public function testShouldGetPath(): void
    {
        $data = new IncludedEntityData('path', 0, true);
        self::assertSame('path', $data->getPath());
    }

    public function testShouldGetIndex(): void
    {
        $data = new IncludedEntityData('path', 123, true);
        self::assertSame(123, $data->getIndex());
    }

    public function testShouldGetIsExisting(): void
    {
        $data = new IncludedEntityData('path', 123, true);
        self::assertTrue($data->isExisting());
    }

    public function testShouldUseUpdateTargetActionForExistingEntityByDefault(): void
    {
        $data = new IncludedEntityData('path', 123, true);
        self::assertEquals(ApiAction::UPDATE, $data->getTargetAction());
    }

    public function testShouldBePossibleToSpecifyTargetAction(): void
    {
        $data = new IncludedEntityData('path', 123, true, ApiAction::CREATE);
        self::assertEquals(ApiAction::CREATE, $data->getTargetAction());
    }

    public function testShouldNormalizedDataBeNullByDefault(): void
    {
        $data = new IncludedEntityData('path', 123, true);
        self::assertNull($data->getNormalizedData());
    }

    public function testShouldSetNormalizedData(): void
    {
        $data = new IncludedEntityData('path', 123, true);
        $normalizedData = ['key' => 'value'];
        $data->setNormalizedData($normalizedData);
        self::assertEquals($normalizedData, $data->getNormalizedData());
    }

    public function testShouldMetadataBeNullByDefault(): void
    {
        $data = new IncludedEntityData('path', 123, true);
        self::assertNull($data->getMetadata());
    }

    public function testShouldSetMetadata(): void
    {
        $data = new IncludedEntityData('path', 123, true);
        $metadata = new EntityMetadata('Test\Entity');
        $data->setMetadata($metadata);
        self::assertSame($metadata, $data->getMetadata());
    }
}
