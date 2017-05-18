<?php

namespace Oro\Bundle\InstallerBundle\Tests\Unit;

use Oro\Bundle\InstallerBundle\Persister\YamlPersister;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class YamlPersisterTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        $fs = new Filesystem();
        $finder = new Finder();

        $fs->remove($finder->files()->in(__DIR__.DIRECTORY_SEPARATOR.'fixtures')->name('*result*'));
    }

    public function testDoNotDropExistingData()
    {
        $persister = new YamlPersister(__DIR__.DIRECTORY_SEPARATOR.'fixtures', 'base');
        $parameters = $persister->parse();

        $persister = new YamlPersister(__DIR__.DIRECTORY_SEPARATOR.'fixtures', 'dump');
        $persister->dump($parameters);

        $this->assertSame(
            Yaml::parse(
                file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_base.yml')
            ),
            Yaml::parse(
                file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_dump.yml')
            )
        );
    }

    public function testDumpToEmptyFile()
    {
        $fs = new Filesystem();
        $fs->copy(
            __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_empty.yml',
            __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_result1.yml'
        );

        $persister = new YamlPersister(__DIR__.DIRECTORY_SEPARATOR.'fixtures', 'result1');
        $persister->dump([]);

        $this->assertSame(
            Yaml::parse(
                file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_result1.yml')
            ),
            ['parameters' => []]
        );
    }

    public function testDumpNoParameters()
    {
        $fs = new Filesystem();
        $fs->copy(
            __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_no_parameters.yml',
            __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_result2.yml'
        );

        $persister = new YamlPersister(__DIR__.DIRECTORY_SEPARATOR.'fixtures', 'result2');
        $persister->dump([]);

        $this->assertSame(
            Yaml::parse(
                file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_result2.yml')
            ),
            [
                'parameters' => [],
                'imports' => [
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
            __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_parameters.yml',
            __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_result3.yml'
        );

        $persister = new YamlPersister(__DIR__.DIRECTORY_SEPARATOR.'fixtures', 'result3');
        $persister->dump(['parameters' => ['key2' => 'val']]);

        $this->assertSame(
            Yaml::parse(
                file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'parameters_result3.yml')
            ),
            [
                'parameters' => [
                    'key2' => 'val',
                ],
                'imports' => [
                    [
                        'resource' => 'Tests/Behat/parameters.yml',
                    ],
                ],
            ]
        );
    }
}
