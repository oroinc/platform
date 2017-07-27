<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * This trait add support of loading YAML test cases in dataproviders
 */
trait LoadTestCaseDataTrait
{
    /**
     * @param string $path
     * @param string $fileNamePattern
     * @return array
     */
    protected function getTestCaseData($path, $fileNamePattern = '*.yml')
    {
        $finder = new Finder();

        $finder
            ->files()
            ->in($path)
            ->name($fileNamePattern);

        $cases = [];

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            yield $file->getRelativePathname() => Yaml::parse($file->getContents());
        }

        return $cases;
    }
}
