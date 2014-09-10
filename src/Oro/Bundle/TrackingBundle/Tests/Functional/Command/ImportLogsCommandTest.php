<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\Command;

use Symfony\Component\Filesystem\Filesystem;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class UpdateSchemaListenerTest extends WebTestCase
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
    }

    protected function tearDown()
    {
        $this->fs->remove($this->directory);
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
}
