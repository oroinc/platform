<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Persister;

use Oro\Bundle\InstallerBundle\Persister\YamlPersister;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class YamlPersisterTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    protected $temporaryDir;

    protected function setUp()
    {
        $this->temporaryDir = $this->getTempDir('YamlPersisterTest');
    }

    public function testDoNotDropExistingData()
    {
        $persister = new YamlPersister(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures', 'base');
        $parameters = $persister->parse();

        \copy(
            __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'parameters_dump.yml',
            $this->temporaryDir . DIRECTORY_SEPARATOR . 'parameters_dump.yml'
        );

        $persister = new YamlPersister($this->temporaryDir, 'dump');
        $persister->dump($parameters);

        self::assertSame(
            Yaml::parse(
                file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_base.yml'),
                Yaml::PARSE_CONSTANT
            ),
            Yaml::parse(
                file_get_contents($this->temporaryDir.DIRECTORY_SEPARATOR.'parameters_dump.yml'),
                Yaml::PARSE_CONSTANT
            )
        );
    }

    public function testDumpToEmptyFile()
    {
        $fs = new Filesystem();
        $fs->copy(
            __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'parameters_empty.yml',
            $this->temporaryDir . DIRECTORY_SEPARATOR . 'parameters_result1.yml'
        );

        $persister = new YamlPersister($this->temporaryDir, 'result1');
        $persister->dump([]);

        self::assertSame(
            Yaml::parse(
                file_get_contents($this->temporaryDir . DIRECTORY_SEPARATOR . 'parameters_result1.yml')
            ),
            ['parameters' => []]
        );
    }

    public function testDumpNoParameters()
    {
        $fs = new Filesystem();
        $fs->copy(
            __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'parameters_no_parameters.yml',
            $this->temporaryDir . DIRECTORY_SEPARATOR . 'parameters_result2.yml'
        );

        $persister = new YamlPersister($this->temporaryDir, 'result2');
        $persister->dump([]);

        self::assertSame(
            Yaml::parse(
                file_get_contents($this->temporaryDir . DIRECTORY_SEPARATOR . 'parameters_result2.yml')
            ),
            [
                'parameters' => [],
                'imports'    => [
                    [
                        'resource' => 'Tests/Behat/parameters.yml',
                    ],
                ],
            ]
        );
    }

    public function testDumpParameters()
    {
        $fs = new Filesystem();
        $fs->copy(
            __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'parameters_parameters.yml',
            $this->temporaryDir . DIRECTORY_SEPARATOR . 'parameters_result3.yml'
        );

        $persister = new YamlPersister($this->temporaryDir, 'result3');
        $persister->dump(['parameters' => ['key2' => 'val']]);

        self::assertSame(
            Yaml::parse(
                file_get_contents($this->temporaryDir . DIRECTORY_SEPARATOR . 'parameters_result3.yml')
            ),
            [
                'parameters' => [
                    'key2' => 'val',
                ],
                'imports'    => [
                    [
                        'resource' => 'Tests/Behat/parameters.yml',
                    ],
                ],
            ]
        );
    }
}
