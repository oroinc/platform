<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

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

    protected function setUp()
    {
        $this->initClient();

        $this->fs = new Filesystem();

        $this->directory = $this
                ->getContainer()
                ->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'tracking';

    }

    protected function tearDown()
    {
        if (!$this->fs->exists($this->directory)) {
            $this->fs->mkdir($this->directory);
        }
    }

    public function testDirectoryEmpty()
    {
        if ($this->fs->exists($this->directory)) {
            $this->fs->remove($this->directory);
        }

        $result = $this->runCommand('oro:cron:import-tracking');
        $this->assertContains('Logs not found', $result);
    }

    public function testFileProcessed()
    {
        $date = new \DateTime();
        $file = $date->modify('-1 day')->format('Ymd-H') . '-60-1.log';

        $this->fs->dumpFile(
            $this->directory . DIRECTORY_SEPARATOR . $file,
            json_encode(['prop' => 'value'])
        );

        $result = $this->runCommand('oro:cron:import-tracking');
        $this->assertContains(sprintf('Successful: "%s"', $file), $result);
    }

    public function testCurrentFileNotProcessed()
    {
        $date = new \DateTime();
        $file = $date->format('Ymd-H') . '-60-1.log';

        $this->fs->dumpFile(
            $this->directory . DIRECTORY_SEPARATOR . $file,
            json_encode(['prop' => 'value'])
        );

        $result = $this->runCommand('oro:cron:import-tracking');
        $this->assertNotContains(sprintf('Successful: "%s"', $file), $result);
    }
}
