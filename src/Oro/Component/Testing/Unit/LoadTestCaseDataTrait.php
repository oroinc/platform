<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * This trait adds a support of loading YAML test cases in data providers.
 */
trait LoadTestCaseDataTrait
{
    private function getTestCaseData(string $path, string $fileNamePattern = '*.yml'): array
    {
        $finder = new Finder();
        $finder->files()->in($path)->name($fileNamePattern);

        $cases = [];

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $cases[$file->getRelativePathname()] = Yaml::parse($file->getContents());
        }

        return $cases;
    }
}
