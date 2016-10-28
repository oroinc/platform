<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

use Symfony\Component\Yaml\Yaml;

class YamlContentUtils
{
    /**
     * @param string $content
     * @return \Generator
     */
    public static function emitLinesWithNodes($content)
    {
        $current = '';
        foreach (explode("\n", $content) as $line) {
            $m = [];
            if (preg_match('/^[ ]{0,}(\w+)(?=:)/', $line, $m)) {
                $current = $m[1];
            }
            yield $current => $line;
        }
    }

    public static function getCallableResourceUpdater()
    {
        return function (
            ConfigResource $configResource,
            GeneratedTranslationResource $translationResource
        ) {
            $content = $configResource->getContent();
            $path = $translationResource->getPath();
            $value = $translationResource->getValue();

            $newContent = '';
            $next = array_shift($path);
            $lastNode = null;
            $matchLines = [];
            foreach (self::emitLinesWithNodes($content) as $current => $line) {
                if ($lastNode) {
                    if ($lastNode === $current || self::isEmptyLine($line)) {
                        $matchLines[] = $line;
                        continue;
                    } else {
                        $firstLine = reset($matchLines);
                        preg_match('/^[ ]+/', $firstLine, $m);

                        $cleaning = function ($line) use ($m) {
                            return preg_replace("/^{$m[0]}/", '', $line);
                        };

                        $parsed = Yaml::parse(implode("\n", array_map($cleaning, $matchLines)));

                        if (trim($parsed[$lastNode], "\n") !== trim($value, "\n")) {
                            throw new \LogicException(
                                sprintf(
                                    'Cant find key %s in %s with value %s. Searching by path %s',
                                    $translationResource->getKey(),
                                    $configResource->getFile()->getRealPath(),
                                    $value,
                                    implode('.', $translationResource->getPath())
                                )
                            );
                        }
                        $lastNode = null;
                        $matchLines = [];
                    }
                }
                if ($current === $next) {
                    $next = array_shift($path);
                    if ($next === null) {
                        $lastNode = $current;
                        $matchLines[] = $line;
                        continue;
                    }
                }
                $newContent .= $line . "\n";
            }

            $configResource->updateContent(preg_replace('/\n\n(?=\Z)/', "\n", $newContent));
        };
    }

    /**
     * @param string $line
     * @return bool
     */
    public static function isEmptyLine($line)
    {
        return self::isBlank($line) || self::isComment($line);
    }

    /**
     * @param string $l
     * @return bool
     */
    private static function isComment($l)
    {
        $lTrimmedLine = ltrim($l, ' ');

        return '' !== $lTrimmedLine && $lTrimmedLine[0] === '#';
    }

    /**
     * @param string $l
     * @return bool
     */
    private static function isBlank($l)
    {
        return '' == trim($l, ' ');
    }
}
