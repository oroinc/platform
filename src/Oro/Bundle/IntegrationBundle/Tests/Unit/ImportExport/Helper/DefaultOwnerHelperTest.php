<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Helper;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class DefaultOwnerHelperTest extends \PHPUnit_Framework_TestCase
{
    const USER_OWNER_FIELD_NAME  = 'owner';
    const USER_OWNER_COLUMN_NAME = 'owner';

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var OwnershipMetadataProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject */
    protected $uow;

    /** @var DefaultOwnerHelper */
    protected $helper;

    public function setUp()
    {
        $this->em               = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->uow              = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()->getMock();
        $this->metadataProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
                ->disableOriginalConstructor()->getMock();

        $this->em->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->helper = new DefaultOwnerHelper($this->em, $this->metadataProvider);
    }

    public function tearDown()
    {
        unset($this->em, $this->uow, $this->metadataProvider, $this->helper);
    }

    /**
     * @dataProvider defaultChannelOwnerProvider
     *
     * @param Channel $channel
     * @param string  $ownerType
     * @param bool    $expectedReload
     * @param bool    $expectedSet
     */
    public function testPopulateChannelOwner(Channel $channel, $ownerType, $expectedReload, $expectedSet)
    {
        $entity = new \stdClass();
        $owner  = $channel->getDefaultUserOwner();

        $doctrineMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()->getMock();
        $this->em->expects($this->any())->method('getClassMetadata')
            ->will($this->returnValue($doctrineMetadata));

        if ($expectedReload) {
            $this->uow->expects($this->once())->method('getEntityState')->with($this->identicalTo($owner))
                ->will($this->returnValue(UnitOfWork::STATE_DETACHED));
            $this->em->expects($this->once())->method('find')
                ->with($this->equalTo(get_class($owner)))
                ->will($this->returnValue($owner));
        }

        $ownerMetadata = new OwnershipMetadata($ownerType, self::USER_OWNER_FIELD_NAME, self::USER_OWNER_COLUMN_NAME);
        $this->metadataProvider->expects($this->any())->method('getMetadata')
            ->with(get_class($entity))
            ->will($this->returnValue($ownerMetadata));

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
            'should set, user given and user owned entity'             => [$channelWithOwner, 'USER', false, true],
            'should set with reload'                                   => [$channelWithOwner, 'USER', true, true],
            'should not set, user not given even if user owned entity' => [$channelEmptyOwner, 'USER', false, false],
            'should not set, user given and BU owned entity'           => [
                $channelWithOwner,
                'BUSINESS_UNIT',
                false,
                false
            ]
        ];
    }
}
