<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Tests\Specification\Statistic;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\Statistic\FilesystemStatisticRepository;

class FilesystemStatisticRepositoryTest extends \PHPUnit\Framework\TestCase
{
    public static string $statFile;
    public static string $appDir;

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        self::$statFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'feature_duration.json';
        self::$appDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'app';
        $stat = [
            'path/to/features/1.feature' => 3,
            'path/to/features/2.feature' => 6,
            'path/to/features/3.feature' => 12,
        ];

        file_put_contents(self::$statFile, json_encode($stat, JSON_THROW_ON_ERROR));
        mkdir(self::$appDir);
    }

    /**
     * @dataProvider featureDurationProvider
     */
    public function testGetFeatureDuration(string $feature, int $expectedDuration)
    {
        $repository = new FilesystemStatisticRepository(self::$appDir);
        $this->assertSame($expectedDuration, $repository->getFeatureDuration($feature));
    }

    public function featureDurationProvider(): array
    {
        return [
            ['1.feature', 7], // average time
            ['o/features/2.feature', 7], // average time
            ['path/to/features/3.feature', 12],
            ['/full/path/to/features/3.feature', 12],
            ['path/to/features/not_exist.feature', 7], // average time
        ];
    }

    #[\Override]
    public static function tearDownAfterClass(): void
    {
        unlink(self::$statFile);
        rmdir(self::$appDir);
    }
}
