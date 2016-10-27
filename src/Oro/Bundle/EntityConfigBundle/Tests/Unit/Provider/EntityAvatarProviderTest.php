<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\EntityAvatarProvider;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\UIBundle\Model\Image;

class EntityAvatarProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAvatarProvider */
    protected $entityAvatarProvider;

    public function setUp()
    {
        $entityConfigs = [
            'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel' => [
                'icon' => 'icon-class',
            ],
            'Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue' => [],
        ];

        $entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnCallback(function ($className) use ($entityConfigs) {
                return isset($entityConfigs[$className]);
            }));
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will($this->returnCallback(function ($className) use ($entityConfigs) {
                return new Config(
                    $this->getMock('Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface'),
                    $entityConfigs[$className]
                );
            }));

        $this->entityAvatarProvider = new EntityAvatarProvider($entityConfigProvider);
    }

    /**
     * @dataProvider avatarImageProvider
     */
    public function testGetAvatarImage($filterName, $entity, $expectedImage)
    {
        $this->assertEquals(
            $expectedImage,
            $this->entityAvatarProvider->getAvatarImage($filterName, $entity)
        );
    }

    public function avatarImageProvider()
    {
        return [
            'entity with icon config' => [
                'filter',
                new EntityConfigModel(),
                new Image(Image::TYPE_ICON, ['class' => 'icon-class']),
            ],
            'entity without icon config' => [
                'filter',
                new ConfigModelIndexValue(),
                null,
            ],
            'entity without config' => [
                'filter',
                new Image(),
                null,
            ],
        ];
    }
}
