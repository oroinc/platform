<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\EventListener\MergeListener;
use Oro\Bundle\ActivityListBundle\Model\MergeModes;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\EntityStub;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Symfony\Contracts\Translation\TranslatorInterface;

class MergeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $entityMetadata;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var MergeListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->activityManager = $this->createMock(ActivityManager::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function (string $id, array $parameters) {
                $result = $id . '_translated';
                if ($parameters) {
                    $result .= ' (';
                    foreach ($parameters as $k => $v) {
                        $result .= sprintf('%s = %s', $k, $v);
                    }
                    $result .= ')';
                }

                return $result;
            });

        $this->entityMetadata = $this->createMock(EntityMetadata::class);
        $this->entityMetadata->expects($this->any())
            ->method('getClassName')
            ->willReturn(EntityStub::class);

        $this->listener = new MergeListener(
            $translator,
            $this->configProvider,
            $this->activityManager
        );
    }

    public function testOnBuildMetadataNoActivities()
    {
        $this->entityMetadata->expects($this->never())
            ->method('addFieldMetadata');

        $this->activityManager->expects($this->once())
            ->method('getActivities')
            ->willReturn([]);

        $this->listener->onBuildMetadata(new EntityMetadataEvent($this->entityMetadata));
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testOnBuildMetadata(array $keys)
    {
        $config = $this->createMock(ConfigInterface::class);
        $this->configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $config->expects($this->exactly(count($keys)))
            ->method('get')
            ->willReturn('label');

        $fieldMetadataItems = [];
        foreach (array_keys($keys) as $key) {
            $fieldMetadataItems[] = [
                new FieldMetadata([
                    'display'       => true,
                    'activity'      => true,
                    'type'          => $key,
                    'field_name'    => $key,
                    'is_collection' => true,
                    'template'      => '@OroActivityList/Merge/value.html.twig',
                    'label'         => 'oro.activity.merge.label_translated (%activity% = label_translated)',
                    'merge_modes'   => [MergeModes::ACTIVITY_UNITE, MergeModes::ACTIVITY_REPLACE]
                ])
            ];
        }
        $this->entityMetadata->expects($this->exactly(count($fieldMetadataItems)))
            ->method('addFieldMetadata')
            ->withConsecutive(...$fieldMetadataItems);

        $this->activityManager->expects($this->once())
            ->method('getActivities')
            ->willReturn($keys);

        $this->listener->onBuildMetadata(new EntityMetadataEvent($this->entityMetadata));
    }

    public function getDataProvider(): array
    {
        return [
            'one'  => [
                'keys' => ['key1' => 'value']
            ],
            'two'  => [
                'keys' => ['key1' => 'value', 'key2' => 'value']
            ],
            'five' => [
                'keys' => [
                    'key1' => 'value',
                    'key2' => 'value',
                    'key3' => 'value',
                    'key4' => 'value',
                    'key5' => 'value'
                ]
            ]
        ];
    }
}
