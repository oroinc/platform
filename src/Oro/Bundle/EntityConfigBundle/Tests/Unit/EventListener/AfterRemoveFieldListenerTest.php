<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Event\AfterRemoveFieldEvent;
use Oro\Bundle\EntityConfigBundle\EventListener\AfterRemoveFieldListener;
use Oro\Component\Testing\Unit\EntityTrait;

class AfterRemoveFieldListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testOnAfterRemove()
    {
        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var AfterRemoveFieldListener $listener */
        $listener = new AfterRemoveFieldListener($doctrineHelper);

        /** @var FieldConfigModel $configFieldModel */
        $configFieldModel = $this->getEntity(FieldConfigModel::class, ['id' => 1]);
        $event = new AfterRemoveFieldEvent($configFieldModel);

        $repository = $this->getMockBuilder(AttributeGroupRelationRepository ::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('removeByFieldId');

        $doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(AttributeGroupRelation::class)
            ->willReturn($repository);

        $listener->onAfterRemove($event);
    }
}
