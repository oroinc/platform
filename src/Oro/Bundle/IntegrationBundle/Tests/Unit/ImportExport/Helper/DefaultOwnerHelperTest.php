<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultOwnerHelperTest extends \PHPUnit\Framework\TestCase
{
    private const USER_OWNER_FIELD_NAME = 'owner';
    private const USER_OWNER_COLUMN_NAME = 'owner';
    private const ORGANIZATION_FIELD_NAME = 'organization';

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var OwnershipMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataProvider;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $uow;

    /** @var DefaultOwnerHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->metadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);

        $this->helper = new DefaultOwnerHelper($registry, $this->metadataProvider);
    }

    /**
     * @dataProvider defaultIntegrationOwnerProvider
     */
    public function testPopulateChannelOwner(
        Integration $integration,
        string $ownerType,
        bool $expectedReload,
        bool $expectedSet,
        bool $expectedSetOrganization = false
    ) {
        $entity = new \stdClass();
        $owner = $integration->getDefaultUserOwner();
        $organization = $integration->getOrganization();

        $doctrineMetadata = $this->createMock(ClassMetadataInfo::class);
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($doctrineMetadata);

        if ($expectedReload) {
            $this->uow->expects($this->once())
                ->method('getEntityState')
                ->with($this->identicalTo($owner))
                ->willReturn(UnitOfWork::STATE_DETACHED);
            $this->em->expects($this->once())
                ->method('find')
                ->with($this->equalTo(get_class($owner)))
                ->willReturn($owner);
        }

        $ownerMetadata = new OwnershipMetadata(
            $ownerType,
            self::USER_OWNER_FIELD_NAME,
            self::USER_OWNER_COLUMN_NAME,
            self::ORGANIZATION_FIELD_NAME
        );
        $this->metadataProvider->expects($this->any())
            ->method('getMetadata')
            ->with(get_class($entity))
            ->willReturn($ownerMetadata);

        if ($expectedSet) {
            $doctrineMetadata->expects($this->once())
                ->method('setFieldValue')
                ->with($this->identicalTo($entity), self::USER_OWNER_FIELD_NAME, $this->identicalTo($owner));
        } elseif ($expectedSetOrganization) {
            $doctrineMetadata->expects($this->once())
                ->method('setFieldValue')
                ->with(
                    $this->identicalTo($entity),
                    self::ORGANIZATION_FIELD_NAME,
                    $this->identicalTo($organization)
                );
        } else {
            $doctrineMetadata->expects($this->never())
                ->method('setFieldValue');
        }

        $this->helper->populateChannelOwner($entity, $integration);
    }

    public function defaultIntegrationOwnerProvider(): array
    {
        $integrationEmptyOwner = new Integration();

        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);
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
