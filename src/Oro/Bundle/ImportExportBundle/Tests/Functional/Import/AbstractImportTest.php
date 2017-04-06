<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Import;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractImportTest extends WebTestCase
{
    /**
     * @var JobExecutor
     */
    protected $jobExecutor;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->repository = self::getContainer()->get('doctrine')->getRepository($this->getEntityName());
        $this->jobExecutor = self::getContainer()->get('oro_importexport.job_executor');
        $this->propertyAccessor = new PropertyAccessor();
    }

    public function testImport()
    {
        $configuration = [
            'import' => [
                'processorAlias' => $this->getProcessorAlias(),
                'entityName' => $this->getEntityName(),
                'filePath' => $this->getFileName(),
            ]
        ];

        $jobResult = $this->jobExecutor->executeJob(
            'import',
            'entity_import_from_csv',
            $configuration
        );

        $this->assertTrue($jobResult->isSuccessful());

        $entities = $this->repository->findBy([], ['id' => 'ASC']);
        $entitiesToAssert = $this->getEntityArray();

        /** We need to add 1, since there is one Customer User in DB */
        $this->assertCount(count($entitiesToAssert) + 1, $entities);
        foreach ($entitiesToAssert as $entityProperties) {
            next($entities); //Let's skip user in DB
            foreach ($entityProperties as $propertyName => $propertyValue) {
                $entityPropertyValue = $this->propertyAccessor->getValue(current($entities), $propertyName);
                $this->assertEquals($propertyValue, $entityPropertyValue);
            }
        }
    }

    /**
     * Returns name of the processor alias. See importexport.yml
     *
     * @return string
     */
    abstract protected function getProcessorAlias();

    /**
     * Returns entity name to be imported, ie: \Oro\Bundle\CustomerBundle\Entity\Customer
     *
     * @return string
     */
    abstract protected function getEntityName();

    /**
     * Returns csv file path used to import
     *
     * @return string
     */
    abstract protected function getFileName();

    /**
     * Returns array of entities used to compare in test, for details check documentation
     *
     * @return array
     */
    abstract protected function getEntityArray();
}
