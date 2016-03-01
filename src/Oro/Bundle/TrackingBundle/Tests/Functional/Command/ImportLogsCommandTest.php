<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\Command;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ImportLogsCommandTest extends WebTestCase
{
    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $directory;

    /**
     * @var string
     */
    protected $logsDir;

    protected function setUp()
    {
        $this->initClient();

        $this->fs = new Filesystem();

        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . time();

        $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->beginTransaction();
    }

    protected function tearDown()
    {
        // clear DB from separate connection, close to avoid connection limit and memory leak
        $manager = $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager();
        $manager->rollback();
        $manager->getConnection()->close();

        $this->fs->remove($this->directory);
        parent::tearDown();
    }

    public function testDirectoryEmpty()
    {
        $this->assertFalse($this->fs->exists($this->directory));

        $result = $this->runCommand(
            'oro:cron:import-tracking',
            ['--directory' => $this->directory]
        );
        $this->assertTrue($this->fs->exists($this->directory));
        $this->assertContains('Logs not found', $result);
    }

    public function testFileProcessed()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $file = $date->modify('-1 day')->format('Ymd-H') . '-60-1.log';

        $this->fs->dumpFile(
            $this->directory . DIRECTORY_SEPARATOR . $file,
            json_encode(['prop' => 'value'])
        );

        $result = $this->runCommand(
            'oro:cron:import-tracking',
            ['--directory' => $this->directory]
        );
        $this->assertFileNotExists($this->directory . DIRECTORY_SEPARATOR . $file);
        $this->assertContains(sprintf('Successful: "%s"', $file), $result);
    }

    public function testCurrentFileNotProcessed()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $file = $date->format('Ymd-H') . '-60-1.log';

        $this->fs->dumpFile(
            $this->directory . DIRECTORY_SEPARATOR . $file,
            json_encode(['prop' => 'value'])
        );

        $result = $this->runCommand(
            'oro:cron:import-tracking',
            ['--directory' => $this->directory]
        );
        $this->assertFileExists($this->directory . DIRECTORY_SEPARATOR . $file);
        $this->assertNotContains(sprintf('Successful: "%s"', $file), $result);
    }

    public function testIsFileProcessed()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $fileName = $date->modify('-1 day')->format('Ymd-H') . '-60-1.log';
        $file = $this->directory . DIRECTORY_SEPARATOR . $fileName;

        $this->fs->dumpFile($file, json_encode(['prop' => 'value']));

        $jobResult = $this->getContainer()->get('oro_importexport.job_executor')->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            'import_log_to_database',
            [
                ProcessorRegistry::TYPE_IMPORT => [
                    'entityName' => $this->getContainer()->getParameter('oro_tracking.tracking_data.class'),
                    'processorAlias' => 'oro_tracking.processor.data',
                    'file' => $file,
                ],
            ]
        );
        $this->assertTrue($jobResult->isSuccessful());

        $result = $this->runCommand(
            'oro:cron:import-tracking',
            ['--directory' => $this->directory]
        );
        $this->assertFileNotExists($this->directory . DIRECTORY_SEPARATOR . $file);
        $this->assertContains(sprintf('"%s" already processed', $fileName), $result);
    }
}
