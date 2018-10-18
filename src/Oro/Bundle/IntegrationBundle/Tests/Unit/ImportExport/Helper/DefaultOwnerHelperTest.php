<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

class DefaultOwnerHelperTest extends \PHPUnit\Framework\TestCase
{
    const USER_OWNER_FIELD_NAME  = 'owner';
    const USER_OWNER_COLUMN_NAME = 'owner';
    const ORGANIZATION_FIELD_NAME = 'organization';

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $metadataProvider;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
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
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface')
                ->disableOriginalConstructor()->getMock();

        $this->em->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $registry = $this->createMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $registry->expects($this->any())->method('getManager')
            ->will($this->returnValue($this->em));

        $this->helper = new DefaultOwnerHelper($registry, $this->metadataProvider);
    }

    public function tearDown()
    {
        unset($this->em, $this->uow, $this->metadataProvider, $this->helper);
    }

    /**
     * @dataProvider defaultIntegrationOwnerProvider
     *
     * @param Integration $integration
     * @param string      $ownerType
     * @param bool        $expectedReload
     * @param bool        $expectedSet
     * @param bool        $expectedSetOrganization
     */
    public function testPopulateChannelOwner(
        Integration $integration,
        $ownerType,
        $expectedReload,
        $expectedSet,
        $expectedSetOrganization = false
    ) {
        $entity = new \stdClass();
        $owner  = $integration->getDefaultUserOwner();
        $organization = $integration->getOrganization();

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

        $ownerMetadata = new OwnershipMetadata(
            $ownerType,
            self::USER_OWNER_FIELD_NAME,
            self::USER_OWNER_COLUMN_NAME,
            self::ORGANIZATION_FIELD_NAME
        );
        $this->metadataProvider->expects($this->any())->method('getMetadata')
            ->with(get_class($entity))
            ->will($this->returnValue($ownerMetadata));

        if ($expectedSet) {
            $doctrineMetadata->expects($this->once())->method('setFieldValue')
                ->with($this->identicalTo($entity), self::USER_OWNER_FIELD_NAME, $this->identicalTo($owner));
        } elseif ($expectedSetOrganization) {
            $doctrineMetadata->expects($this->once())->method('setFieldValue')
                ->with(
                    $this->identicalTo($entity),
                    self::ORGANIZATION_FIELD_NAME,
                    $this->identicalTo($organization)
                );
        } else {
            $doctrineMetadata->expects($this->never())->method('setFieldValue');
        }

        $this->helper->populateChannelOwner($entity, $integration);
    }

    /**
     * @return array
     */
    public function defaultIntegrationOwnerProvider()
    {
        $integrationEmptyOwner = new Integration();

        $user                 = $this->createMock('Oro\Bundle\UserBundle\Entity\User');
        $organization        = $this->createMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $integrationWithOwner = new Integration();
        $integrationWithOwner->setDefaultUserOwner($user);

        $integrationWithOrganization = new Integration();
        $integrationWithOrganization->setOrganization($organization);

        return [
            'should set, user given and user owned entity'             => [$integrationWithOwner, 'USER', false, true],
            'should set with reload'                                   => [$integrationWithOwner, 'USER', true, true],
            'should not set, user not given even if user owned entity' => [
                $integrationEmptyOwner,
                'USER',
                false,
                false
            ],
            'should not set, user given and BU owned entity'           => [
                $integrationWithOwner,
                'BUSINESS_UNIT',
                false,
                false
            ],
            'set organization'                                         => [
                $integrationWithOrganization,
                'BUSINESS_UNIT',
                false,
                false,
                true
            ]
        ];
    }
}
