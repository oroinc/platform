<?php

namespace Oro\Bundle\CronBundle\ORM;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class CommandArgsTokenizer
{
    const REGEX_STRING = '([^\s]+?)(?:\s|(?<!\\\\)"|(?<!\\\\)\'|$)';
    const REGEX_QUOTED_STRING = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')';

    /** @var CommandArgsNormalizer[] */
    protected $normalizers = [];

    /**
     * Registers a normalizer
     *
     * @param CommandArgsNormalizer $normalizer
     */
    public function addNormalizer(CommandArgsNormalizer $normalizer)
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
    public function tokenize($str, AbstractPlatform $platform)
    {
        $normalizer = $this->getNormalizer($platform);

        $tokens = [];
        $length = strlen($str);
        $i      = 0;
        while ($i < $length) {
            if (preg_match('/\s+/A', $str, $match, null, $i)) {
                // skip whitespaces
            } elseif (preg_match('/([^="\'\s]+?)(=?)(' . self::REGEX_QUOTED_STRING . '+)/A', $str, $match, null, $i)) {
                $tokens[] =
                    $match[1]
                    . $match[2]
                    . $normalizer->quoteArgValue(
                        $normalizer->normalize(substr($match[3], 1, strlen($match[3]) - 2))
                    );
            } elseif (preg_match('/' . self::REGEX_QUOTED_STRING . '/A', $str, $match, null, $i)) {
                $tokens[] = $normalizer->quoteArg(
                    $normalizer->normalize(substr($match[0], 1, strlen($match[0]) - 2))
                );
            } elseif (preg_match('/' . self::REGEX_STRING . '/A', $str, $match, null, $i)) {
                $tokens[] = $normalizer->normalize($match[1]);
            } else {
                // should never happen
                throw new \InvalidArgumentException(
                    sprintf('Unable to parse input near "... %s ..."', substr($str, $i, 10))
                );
            }

            $i += strlen($match[0]);
        }

        return $tokens;
    }

    /**
     * @param AbstractPlatform $platform
     *
     * @return CommandArgsNormalizer
     *
     * @throws \InvalidArgumentException if there is no normalizer for the given database platform
     */
    protected function getNormalizer(AbstractPlatform $platform)
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
