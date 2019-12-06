<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Extension;

use DeepCopy\Filter\SetNullFilter;
use Oro\Bundle\DraftBundle\Duplicator\DraftContext;
use Oro\Bundle\DraftBundle\Duplicator\Extension\OwnershipExtension;
use Oro\Bundle\DraftBundle\Duplicator\Matcher\PropertiesNameMatcher;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Manager\DraftManager;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class OwnershipExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var OwnershipExtension */
    private $ownershipExtension;

    /**
     * @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ownershipMetadataProvider;

    protected function setUp(): void
    {
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->ownershipExtension = new OwnershipExtension($this->ownershipMetadataProvider);
    }

    public function testIsSupport(): void
    {
        $ownershipMetadata = $this->getEntity(
            OwnershipMetadata::class,
            ['ownerType' => OwnershipMetadata::OWNER_TYPE_USER]
        );
        $this->ownershipMetadataProvider
            ->expects($this->once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);

        /** @var DraftableInterface $source */
        $source = $this->getEntity(DraftableEntityStub::class);
        $context = new DraftContext();
        $context->offsetSet('source', $source);
        $context->offsetSet('action', DraftManager::ACTION_CREATE_DRAFT);
        $this->ownershipExtension->setContext($context);

        $this->assertTrue($this->ownershipExtension->isSupport($source));
    }

    public function testGetFilter(): void
    {
        $this->assertEquals($this->ownershipExtension->getFilter(), new SetNullFilter());
    }

    public function testGetMatcher(): void
    {
        $properties = ['ownerFieldName', 'organizationFieldName'];
        $ownershipMetadata = $this->getEntity(
            OwnershipMetadata::class,
            [
                'ownerFieldName' => 'ownerFieldName',
                'organizationFieldName' => 'organizationFieldName'
            ]
        );
        $this->ownershipMetadataProvider
            ->expects($this->once())
            ->method('getMetadata')
            ->willReturn($ownershipMetadata);

        $context = new DraftContext();
        $context->offsetSet('source', $this->getEntity(DraftableEntityStub::class));
        $this->ownershipExtension->setContext($context);

        $this->assertEquals($this->ownershipExtension->getMatcher(), new PropertiesNameMatcher($properties));
    }
}
