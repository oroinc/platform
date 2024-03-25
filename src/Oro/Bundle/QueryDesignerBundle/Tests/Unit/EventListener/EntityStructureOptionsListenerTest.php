<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Model\EntityFieldStructure;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\QueryDesignerBundle\EventListener\EntityStructureOptionsListener;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityStructureOptionsListenerTest extends TestCase
{
    private EntityStructureOptionsListener $listener;

    private EntityAliasResolver|MockObject $entityAliasResolver;

    protected function setUp(): void
    {
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->listener = new EntityStructureOptionsListener($this->entityAliasResolver);
    }

    public function testOnOptionsRequest(): void
    {
        $entityStructure = new EntityStructure();
        $entityStructure->setClassName(CmsUser::class);
        $entityStructure->setAlias('cms_user');
        $entityStructure->setPluralAlias('cms_users');

        $entityStructureToCheck = new EntityStructure();
        $entityStructureToCheck->setClassName(CmsAddress::class);

        $field = new EntityFieldStructure();
        $field->setName('simple_field');
        $entityStructureToCheck->addField($field);

        $fieldWithNormalizedName = new EntityFieldStructure();
        $fieldWithNormalizedName->setName(sprintf('%s::address_a12cd34', CmsUser::class));
        $fieldWithNormalizedName->setNormalizedName('Normalized_Name');
        $entityStructureToCheck->addField($fieldWithNormalizedName);

        $fieldWithoutNormalizedName = new EntityFieldStructure();
        $fieldWithoutNormalizedName->setName(sprintf('%s::address_c456ed4', CmsUser::class));
        $entityStructureToCheck->addField($fieldWithoutNormalizedName);

        $fieldWithEntityAlias = new EntityFieldStructure();
        $fieldWithEntityAlias->setName(sprintf('%s::address_b716fa3', CmsAddress::class));
        $entityStructureToCheck->addField($fieldWithEntityAlias);

        $fieldWithoutEntityAlias = new EntityFieldStructure();
        $fieldWithoutEntityAlias->setName(sprintf('%s::std_a366db2', \stdClass::class));
        $entityStructureToCheck->addField($fieldWithoutEntityAlias);

        $this->entityAliasResolver
            ->expects(self::exactly(2))
            ->method('hasAlias')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->entityAliasResolver
            ->expects(self::exactly(1))
            ->method('getAlias')
            ->willReturn('cms_address');

        $event = $this->createMock(EntityStructureOptionsEvent::class);
        $event->expects(self::once())
            ->method('getData')
            ->willReturn([$entityStructure, $entityStructureToCheck]);
        $event->expects(self::once())
            ->method('setData');

        $this->listener->onOptionsRequest($event);

        self::assertEquals([
            'simple_field',
            'Normalized_Name',
            'cms_user_address_c456ed4',
            'cms_address_address_b716fa3',
            'stdclass_std_a366db2',
        ], array_map(
            fn (EntityFieldStructure $field) => $field->getNormalizedName(),
            $entityStructureToCheck->getFields()
        ));
    }
}
