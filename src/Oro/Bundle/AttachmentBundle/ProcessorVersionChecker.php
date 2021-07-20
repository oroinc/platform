<?php

namespace Oro\Bundle\AttachmentBundle;

use Composer\Semver\Semver;
use Symfony\Component\Process\Process;

/**
 * Check version of installed JPEGOptim or PNGQuant libraries.
 */
class ProcessorVersionChecker
{
    public const PNGQUANT_VERSION = '>=2.5.0';
    public const JPEGOPTIM_VERSION = '>=1.4.0';

    public static function satisfies(string $executable): bool
    {
        $executableParts = explode('/', $executable);
        [$library, $version] = self::getLibraryInfo(end($executableParts));

        $process = new Process([$executable, '-V']);
        $process->run();
        if (!$process->isSuccessful()) {
            return false;
        }
        $output = $process->getOutput();
        $strings = explode(PHP_EOL, $output);
        preg_match(self::getVersionPattern($library), $strings[0], $matches);

        return Semver::satisfies($matches[0], $version);
    }

    public static function getLibraryInfo(string $library): array
    {
        return $library === ProcessorHelper::JPEGOPTIM
            ? [ProcessorHelper::JPEGOPTIM, self::JPEGOPTIM_VERSION]
            : [ProcessorHelper::PNGQUANT, self::PNGQUANT_VERSION];
    }

    private static function getVersionPattern(string $library): string
    {
        $versionPrefix = $library === ProcessorHelper::JPEGOPTIM ? 'v' : '';

        return sprintf('/(%s(\d+\.)(\d+\.)(\*|\d+))/', $versionPrefix);
    }
}
