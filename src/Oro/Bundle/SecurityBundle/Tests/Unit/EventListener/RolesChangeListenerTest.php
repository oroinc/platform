<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\SecurityBundle\EventListener\RolesChangeListener;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestBusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestOrganization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity\TestUser;
use Oro\Component\Testing\Unit\ORM\Mocks\StatementMock;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class RolesChangeListenerTest extends OrmTestCase
{
    private const ENTITY_NAMESPACE = 'Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity';

    /** @var EntityManagerInterface */
    private $em;

    /** @var AdapterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(AdapterInterface::class);
        $this->em = $this->getTestEntityManager();

        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $listener = new RolesChangeListener('organizations');
        $listener->addSupportedClass(self::ENTITY_NAMESPACE . '\TestUser');
        $this->em->getEventManager()->addEventListener('onFlush', $listener);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQueryCacheImpl(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    public function testOnFlushWithNotSupportedEntity()
    {
        $this->cache->expects(self::never())
            ->method('clear');

        $connection = $this->getDriverConnectionMock($this->em);
        $connection->expects(self::once())
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

        $connection = $this->getDriverConnectionMock($this->em);
        $connection->expects(self::exactly(3))
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
        $this->addQueryExpectation(
            'INSERT INTO tbl_user_to_organization (user_id, organization_id) VALUES (?, ?)',
            null,
            [1 => 1, 2 => 10],
            [1 => \PDO::PARAM_INT, 2 => \PDO::PARAM_INT],
            1
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $user = $this->em->getRepository(self::ENTITY_NAMESPACE . '\TestUser')->find(1);
        $organization = $this->em->getReference(self::ENTITY_NAMESPACE . '\TestOrganization', 10);
        $user->addOrganization($organization);
        $this->em->flush();
    }
}
