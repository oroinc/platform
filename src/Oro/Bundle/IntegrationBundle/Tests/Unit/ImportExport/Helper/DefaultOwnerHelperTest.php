<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Helper;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class DefaultOwnerHelperTest extends \PHPUnit_Framework_TestCase
{
    const USER_OWNER_FIELD_NAME  = 'owner';
    const USER_OWNER_COLUMN_NAME = 'owner';
    const TEST_ENTITY_CLASS_NAME = 'TestOrg/TestBundle/Entity/TestEntity';

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var OwnershipMetadataProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var DefaultOwnerHelper */
    protected $helper;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->metadataProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
                ->disableOriginalConstructor()->getMock();

        $this->helper = new DefaultOwnerHelper($this->em, $this->metadataProvider);
    }

    public function tearDown()
    {
        unset($this->em, $this->metadataProvider, $this->helper);
    }

    /**
     * @dataProvider defaultChannelOwnerProvider
     *
     * @param Channel $channel
     * @param string  $ownerType
     * @param bool    $expectedSet
     */
    public function testPopulateChannelOwner(Channel $channel, $ownerType, $expectedSet)
    {
        $entity = new \stdClass();

        $doctrineMetadata       = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()->getMock();
        $doctrineMetadata->name = self::TEST_ENTITY_CLASS_NAME;

        $this->em->expects($this->any())->method('getClassMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $ownerMetadata = new OwnershipMetadata($ownerType, self::USER_OWNER_FIELD_NAME, self::USER_OWNER_COLUMN_NAME);
        $this->metadataProvider->expects($this->any())->method('getMetadata')
            ->with(self::TEST_ENTITY_CLASS_NAME)
            ->will($this->returnValue($ownerMetadata));

        $owner = $channel->getDefaultUserOwner();
        if ($expectedSet) {
            $doctrineMetadata->expects($this->once())->method('setFieldValue')
                ->with($this->identicalTo($entity), self::USER_OWNER_FIELD_NAME, $this->identicalTo($owner));
        } else {
            $doctrineMetadata->expects($this->never())->method('setFieldValue');
        }

        $this->helper->populateChannelOwner($entity, $channel);
    }

    /**
     * @return array
     */
    public function defaultChannelOwnerProvider()
    {
        $channelEmptyOwner = new Channel();

        $user             = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $channelWithOwner = new Channel();
        $channelWithOwner->setDefaultUserOwner($user);

        return [
            'should set, user given and user owned entity'             => [$channelWithOwner, 'USER', true],
            'should not set, user not given even if user owned entity' => [$channelEmptyOwner, 'USER', false],
            'should not set, user given and BU owned entity'           => [$channelWithOwner, 'BUSINESS_UNIT', false]
        ];
    }
}
