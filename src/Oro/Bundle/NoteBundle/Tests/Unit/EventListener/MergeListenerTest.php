<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\NoteBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\NoteBundle\EventListener\MergeListener;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;

class MergeListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var MergeListener */
    protected $listener;

    /** @var EntityMetadata|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityMetadata;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var ActivityListChainProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $activityListChainProvider;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->activityListChainProvider = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('Items');

        $this->listener = new MergeListener(
            $this->translator,
            $this->configProvider,
            $this->activityListChainProvider
        );

        $this->entityMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->setMethods(['getClassName', 'addFieldMetadata'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadata
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue(get_class($this->createEntity())));
    }

    public function testOnBuildMetadataNoActivities()
    {
        $this->entityMetadata
            ->expects($this->never())
            ->method('addFieldMetadata');

        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->listener->onBuildMetadata($event);
    }

    public function testOnBuildMetadata()
    {
        $entity = Note::ENTITY_NAME;
        $alias = 'oro_bundle_notebundle_entity_note';
        $calls = 1;

        $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $config->expects($this->exactly($calls))
            ->method('get')
            ->willReturn('label');

        $fieldMetadataOptions = [
            'display' => true,
            'activity' => true,
            'type' => $entity,
            'field_name' => $alias,
            'is_collection' => true,
            'template' => 'OroActivityListBundle:Merge:value.html.twig',
            'label' => 'Items',
            'merge_modes' =>
                [MergeModes::NOTES_UNITE, MergeModes::NOTES_REPLACE]
        ];
        $fieldMetadata = new FieldMetadata($fieldMetadataOptions);

        $this->entityMetadata
            ->expects($this->at(1))
            ->method('addFieldMetadata')
            ->with($this->equalTo($fieldMetadata));

        $this->activityListChainProvider
            ->expects($this->exactly($calls))
            ->method('isApplicableTarget')
            ->willReturn(true);

        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->listener->onBuildMetadata($event);
    }

    /**
     * @param mixed $id
     *
     * @return EntityStub
     */
    protected function createEntity($id = null)
    {
        return new EntityStub($id);
    }
}
