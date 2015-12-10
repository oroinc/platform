<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\ActivityListBundle\Model\MergeModes;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\ActivityListBundle\EventListener\MergeListener;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;

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

    /** @var ActivityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $activityManager;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->activityManager = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('Items');

        $this->listener = new MergeListener(
            $this->translator,
            $this->configProvider,
            $this->activityManager
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

        $this->activityManager
            ->expects($this->once())
            ->method('getActivities')
            ->willReturn([]);

        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->listener->onBuildMetadata($event);
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testOnBuildMetadata($keys, $calls)
    {
        $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $config->expects($this->exactly($calls))
            ->method('get')
            ->willReturn('label');

        $i = 0;
        $k = array_keys($keys);
        foreach ($k as $key) {
            $i++;
            $fieldMetadataOptions = [
                'display' => true,
                'activity' => true,
                'type' => $key,
                'field_name' => $key,
                'is_collection' => true,
                'template' => 'OroActivityListBundle:Merge:value.html.twig',
                'label' => 'Items',
                'merge_modes' => [MergeModes::ACTIVITY_UNITE, MergeModes::ACTIVITY_REPLACE]
            ];
            $fieldMetadata = new FieldMetadata($fieldMetadataOptions);

            $this->entityMetadata
                ->expects($this->at($i))
                ->method('addFieldMetadata')
                ->with($this->equalTo($fieldMetadata));
        }

        $this->activityManager
            ->expects($this->once())
            ->method('getActivities')
            ->willReturn($keys);

        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->listener->onBuildMetadata($event);
    }

    public function getDataProvider()
    {
        return [
            'one' => [
                'keys' => ['key1' => 'value'],
                'calls' => 1
            ],
            'two' => [
                'keys' => ['key1' => 'value', 'key2' => 'value'],
                'calls' => 2
            ],
            'five' => [
                'keys' => [
                    'key1' => 'value',
                    'key2' => 'value',
                    'key3' => 'value',
                    'key4' => 'value',
                    'key5' => 'value'
                ],
                'calls' => 5
            ],
        ];
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
