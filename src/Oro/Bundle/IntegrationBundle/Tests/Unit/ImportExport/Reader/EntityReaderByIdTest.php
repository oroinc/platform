<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Reader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Provider\ExportQueryProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Reader\EntityReaderById;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class EntityReaderByIdTest extends OrmTestCase
{
    const TEST_ENTITY_ID = 11;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $managerRegistry;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextRegistry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var EntityReaderById */
    protected $reader;

    /** @var ExportQueryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $exportQueryProvider;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->managerRegistry = $this->createMock('Doctrine\Persistence\ManagerRegistry');
        $reader = new AnnotationReader();
        $metadataDriver = new AnnotationDriver($reader, 'Oro\Bundle\IntegrationBundle\Entity');
        $this->exportQueryProvider = $this->createMock(ExportQueryProvider::class);

        $this->em = $this->getTestEntityManager();
        $config = $this->em->getConfiguration();
        $config->setMetadataDriverImpl($metadataDriver);
        $config->setEntityNamespaces(['OroIntegrationBundle' => 'Oro\Bundle\IntegrationBundle\Entity']);

        $this->reader = new EntityReaderById(
            $this->contextRegistry,
            $managerRegistry,
            $ownershipMetadataProvider
        );
        $this->reader->setExportQueryProvider($this->exportQueryProvider);
    }

    protected function tearDown(): void
    {
        unset($this->reader, $this->em, $this->managerRegistry, $this->contextRegistry);
    }

    public function testInitialization()
    {
        $entityName = 'OroIntegrationBundle:Channel';
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from($entityName, 'e');

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')->getMock();
        $context->expects($this->any())
            ->method('hasOption')
            ->will(
                $this->returnValueMap(
                    [
                        ['entityName', false],
                        ['queryBuilder', true],
                        [EntityReaderById::ID_FILTER, true]
                    ]
                )
            );
        $context->expects($this->any())
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    [
                        ['queryBuilder', null, $qb],
                        [EntityReaderById::ID_FILTER, null, self::TEST_ENTITY_ID]
                    ]
                )
            );

        $this->reader->setStepExecution($this->getMockStepExecution($context));
        $this->assertSame('SELECT e FROM OroIntegrationBundle:Channel e WHERE o.id = :id', $qb->getDQL());
        $this->assertSame(self::TEST_ENTITY_ID, $qb->getParameter('id')->getValue());
    }

    /**
     * @param mixed $context
     *
     * @return \PHPUnit\Framework\MockObject\MockObject+
     */
    protected function getMockStepExecution($context)
    {
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        return $stepExecution;
    }
}
