<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Reader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Reader\EntityReaderById;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Bridge\Doctrine\ManagerRegistry;

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

    public function setUp()
    {
        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->setMethods(array('getByStepExecution'))
            ->getMock();

        $this->managerRegistry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $reader                = new AnnotationReader();
        $metadataDriver        = new AnnotationDriver(
            $reader,
            'Oro\Bundle\IntegrationBundle\Entity'
        );

        $ownershipMetadataProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface')
                ->disableOriginalConstructor()
                ->getMock();

        $this->em = $this->getTestEntityManager();
        $config   = $this->em->getConfiguration();
        $config->setMetadataDriverImpl($metadataDriver);
        $config->setEntityNamespaces(['OroIntegrationBundle' => 'Oro\Bundle\IntegrationBundle\Entity']);

        $this->reader = new EntityReaderById(
            $this->contextRegistry,
            $this->managerRegistry,
            $ownershipMetadataProvider
        );
    }

    protected function tearDown()
    {
        unset($this->reader, $this->em, $this->managerRegistry, $this->contextRegistry);
    }

    public function testInitialization()
    {
        $entityName = 'OroIntegrationBundle:Channel';
        $qb         = $this->em->createQueryBuilder()
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
