<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\EventListener\RolesChangeListener;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestBusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestOrganization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestUser;
use Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock;
use Oro\Component\TestUtils\ORM\Mocks\StatementMock;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class RolesChangeListenerTest extends OrmTestCase
{
    private const ENTITY_NAMESPACE = 'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity';

    /** @var EntityManagerMock */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $conn;

    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var RolesChangeListener */
    private $listener;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(AbstractAdapter::class);
        $this->em = $this->getTestEntityManager();
        $reader = new AnnotationReader();
        $doctrine = $this->createMock(ManagerRegistry::class);

        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver($reader, self::ENTITY_NAMESPACE));
        $this->em->getConfiguration()->setEntityNamespaces(['Test' => self::ENTITY_NAMESPACE]);

        $this->conn = $this->getDriverConnectionMock($this->em);

        $this->listener = new RolesChangeListener('organizations');
        $this->listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser');
        $this->em->getEventManager()->addEventListener('onFlush', $this->listener);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryCacheImpl()
    {
        return $this->cache;
    }

    public function testOnFlushWithNotSupportedEntity()
    {
        $this->cache->expects(self::never())
            ->method('clear');

        $this->conn->expects(self::once())
            ->method('prepare')
            ->willReturn($this->createMock(StatementMock::class));

        $entity = new TestBusinessUnit();
        $this->em->persist($entity);
        $this->em->flush();
    }

    public function testOnFlushOnInsertSupportedEntity()
    {
        $this->cache->expects(self::once())
            ->method('clear');

        $this->conn->expects(self::exactly(2))
            ->method('prepare')
            ->willReturn($this->createMock(StatementMock::class));

        $entity = new TestUser();
        $organization = new TestOrganization();
        $entity->addOrganization($organization);
        $this->em->persist($entity);
        $this->em->persist($organization);
        $this->em->flush();
    }

    public function testOnFlushOnUpdateSupportedEntity()
    {
        $this->cache->expects(self::once())
            ->method('clear');

        $this->addQueryExpectation(
            'SELECT t0.id AS id_1, t0.username AS username_2, t0.owner_id AS owner_id_3'
            . ' FROM tbl_user t0 WHERE t0.id = ?',
            [['id_1' => 1, 'username_2' => 'test', 'owner_id_3' => 12]],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->addQueryExpectation(
            'SELECT t0.id AS id_1 FROM tbl_organization t0 INNER JOIN tbl_user_to_organization'
            . ' ON t0.id = tbl_user_to_organization.organization_id WHERE tbl_user_to_organization.user_id = ?',
            [],
            [1 => 1],
            [1 => \PDO::PARAM_INT]
        );
        $this->applyQueryExpectations($this->conn);

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find(1);
        $organization = $this->em->getReference(self::ENTITY_NAMESPACE . '\TestOrganization', 10);
        $user->addOrganization($organization);
        $this->em->flush();
    }
}
