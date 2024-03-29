<?php

namespace Oro\Bundle\CronBundle\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * CLI command command arguments tokenizer.
 */
class CommandArgsTokenizer
{
    private const REGEX_STRING = '([^\s]+?)(?:\s|(?<!\\\\)"|(?<!\\\\)\'|$)';
    private const REGEX_QUOTED_STRING = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')';

    /** @var CommandArgsNormalizer[] */
    private array $normalizers = [];

    /**
     * Registers a normalizer.
     */
    public function addNormalizer(CommandArgsNormalizer $normalizer): void
    {
        $this->normalizers[] = $normalizer;
    }

    /**
     * @param string           $str      The input string to tokenize
     * @param AbstractPlatform $platform The database platform where tokens will be used
     *
     * @return string[] An array of tokens
     *
     * @throws \InvalidArgumentException When unable to parse the input string
     */
    public function tokenize(string $str, AbstractPlatform $platform): array
    {
        $normalizer = $this->getNormalizer($platform);

        $tokens = [];
        $length = \strlen($str);
        $i = 0;
        while ($i < $length) {
            if (preg_match('/\s+/A', $str, $match, 0, $i)) {
                // skip whitespaces
            } elseif (preg_match('/([^="\'\s]+?)(=?)(' . self::REGEX_QUOTED_STRING . '+)/A', $str, $match, 0, $i)) {
                $tokens[] =
                    $match[1]
                    . $match[2]
                    . $normalizer->quoteArgValue(
                        $normalizer->normalize(substr($match[3], 1, -1))
                    );
            } elseif (preg_match('/' . self::REGEX_QUOTED_STRING . '/A', $str, $match, 0, $i)) {
                $tokens[] = $normalizer->quoteArg(
                    $normalizer->normalize(substr($match[0], 1, -1))
                );
            } elseif (preg_match('/' . self::REGEX_STRING . '/A', $str, $match, 0, $i)) {
                $tokens[] = $normalizer->normalize($match[1]);
            } else {
                // should never happen
                throw new \InvalidArgumentException(
                    sprintf('Unable to parse input near "... %s ..."', substr($str, $i, 10))
                );
            }

            $i += \strlen($match[0]);
        }

        return $tokens;
    }

    /**
     * @throws \InvalidArgumentException if there is no normalizer for the given database platform
     */
    private function getNormalizer(AbstractPlatform $platform): CommandArgsNormalizer
    {
        for ($i = count($this->normalizers) - 1; $i >= 0; $i--) {
            if ($this->normalizers[$i]->supports($platform)) {
                return $this->normalizers[$i];
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported database platform: %s', $platform->getName())
        );
    }
}
