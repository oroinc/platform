<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Export;

use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractExportTest extends WebTestCase
{
    /**
     * @var JobExecutor
     */
    protected $jobExecutor;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * Stores content of the exported CSV file
     * @var string
     */
    protected $fileContent;

    protected function setUp()
    {
        $this->initClient();
        $this->jobExecutor = self::getContainer()->get('oro_importexport.job_executor');
        $this->filePath = FileManager::generateTmpFilePath(
            FileManager::generateFileName($this->getProcessorAlias(), 'csv')
        );
    }

    protected function tearDown()
    {
        @unlink($this->filePath);
    }

    public function testExport()
    {
        $configuration = [
            'export' => [
                'processorAlias' => $this->getProcessorAlias(),
                'entityName' => $this->getEntityName(),
                'filePath' => $this->filePath,
            ]
        ];

        $jobResult = $this->jobExecutor->executeJob(
            'export',
            'entity_export_to_csv',
            $configuration
        );

        $this->assertTrue($jobResult->isSuccessful());

        $this->assertFileExists($this->filePath);
        $this->fileContent = file_get_contents($this->filePath);

        foreach ($this->getNotContains() as $notContain) {
            $this->assertNotContains($notContain, $this->fileContent);
        }

        foreach ($this->getContains() as $contain) {
            $this->assertContains($contain, $this->fileContent);
        }

        $this->assertSame($this->getExpectedNumberOfLines(), $jobResult->getContext()->getReadCount());
    }


    /**
     * Returns name of the processor alias. See importexport.yml
     *
     * @return string
     */
    abstract protected function getProcessorAlias();

    /**
     * Return entity name to be exported, ie: \Oro\Bundle\CustomerBundle\Entity\Customer
     *
     * @return string
     */
    abstract protected function getEntityName();

    /**
     * Return array of strings, that need to be inside csv file to be correct
     *
     * @return string[]
     */
    abstract protected function getContains();

    /**
     * Return array of string, that can not be in csv file
     *
     * @return string[]
     */
    abstract protected function getNotContains();

    /**
     * Return expected number of lines in csv file.
     *
     * @return integer
     */
    abstract protected function getExpectedNumberOfLines();
}
