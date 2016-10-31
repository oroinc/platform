<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\EntityAvatarProvider;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\IntegrationAwareEntity;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\IntegrationTypeWithIcon;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\IntegrationTypeWithoutIcon;
use Oro\Bundle\UIBundle\Model\Image;

class EntityAvatarProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAvatarProvider */
    protected $entityAvatarProvider;

    public function setUp()
    {
        $typesRegistry = (new TypesRegistry())
            ->addChannelType('without-icon', new IntegrationTypeWithoutIcon())
            ->addChannelType('with-icon', new IntegrationTypeWithIcon());

        $cacheManager = $this->getMockBuilder('Liip\ImagineBundle\Imagine\Cache\CacheManager')
            ->disableOriginalConstructor()
            ->getMock();
        $cacheManager
            ->expects($this->any())
            ->method('getBrowserPath')
            ->will($this->returnArgument(0));

        $this->entityAvatarProvider = new EntityAvatarProvider($typesRegistry, $cacheManager);
    }

    /**
     * @dataProvider getAvatarImageProvider
     */
    public function testGetAvatarImage($entity, Image $expectedImage = null)
    {
        $this->assertEquals(
            $expectedImage,
            $this->entityAvatarProvider->getAvatarImage('filter', $entity)
        );
    }

    public function getAvatarImageProvider()
    {
        return [
            'integration aware entity related to channel with icon' => [
                (new IntegrationAwareEntity())
                    ->setChannel(
                        (new Channel())
                            ->setType('with-icon')
                    ),
                new Image(Image::TYPE_FILE_PATH, ['path' => 'bundles/acmedemo/img/logo.png']),
            ],
            'integration aware entity related to channel without icon' => [
                (new IntegrationAwareEntity())
                    ->setChannel(
                        (new Channel())
                            ->setType('without-icon')
                    ),
                null,
            ],
            'integration aware entity without relation to channel' => [
                new IntegrationAwareEntity(),
                null,
            ],
            'an entity' => [
                new Status(),
                null,
            ],
        ];
    }
}
