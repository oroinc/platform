<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Duplicates logic of Symfony\Component\Translation\IdentityTranslator excepting the case when there are no matching
 * plural form: this class logs a warning and returns the last applicable plural form instead of throwing an exception.
 */
class IdentityTranslator implements TranslatorInterface, LoggerAwareInterface
{
    use TranslatorTrait {
        trans as private doTrans;
    }
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        try {
            return $this->doTrans($id, $parameters, $domain, $locale);
        } catch (\InvalidArgumentException | InvalidArgumentException $throwable) {
            $number = (float)$parameters['%count%'];
            $locale = (string)$locale ?: $this->getLocale();

            $message = sprintf(
                'Unable to choose a translation for "%s" with locale "%s" for value "%d". Double check that this ' .
                'translation has the correct plural options (e.g. "There is one apple|There are %%count%% apples").',
                $id,
                $locale,
                $number
            );
            $this->logger->warning($message);

            return $this->transLastPluralOption($id, $parameters, $locale);
        }
    }

    /**
     * The implementation of this method originates from Symfony\Contracts\Translation\TranslatorTrait
     */
    private function transLastPluralOption(string $id, array $parameters = [], string $locale = null): string
    {
        $number = (float)$parameters['%count%'];
        $locale = (string)$locale ?: $this->getLocale();
        $parts = $this->getPluralParts($id);

        $intervalRegexp = <<<'EOF'
/^(?P<interval>
    ({\s*
        (\-?\d+(\.\d+)?[\s*,\s*\-?\d+(\.\d+)?]*)
    \s*})

        |

    (?P<left_delimiter>[\[\]])
        \s*
        (?P<left>-Inf|\-?\d+(\.\d+)?)
        \s*,\s*
        (?P<right>\+?Inf|\-?\d+(\.\d+)?)
        \s*
    (?P<right_delimiter>[\[\]])
)\s*(?P<message>.*?)$/xs
EOF;

        $standardRules = [];
        $explicitRules = [];
        foreach ($parts as $part) {
            $part = trim(str_replace('||', '|', $part));

            // try to match an explicit rule, then fallback to the standard ones
            if (preg_match($intervalRegexp, $part, $matches)) {
                $explicitRules[] = $matches['message'];
            } elseif (preg_match('/^\w+\:\s*(.*?)$/', $part, $matches)) {
                $standardRules[] = $matches[1];
            } else {
                $standardRules[] = $part;
            }
        }

        $position = $this->getPluralizationRule($number, $locale);
        $lastPosition = $position;
        do {
            if (isset($standardRules[$lastPosition])) {
                return strtr($standardRules[$lastPosition], $parameters);
            }
            $lastPosition--;
        } while ($lastPosition > 0);

        return $explicitRules ? strtr(end($explicitRules), $parameters) : $id;
    }

    private function getPluralParts(string $id): array
    {
        $parts = [];
        if (preg_match('/^\|++$/', $id)) {
            $parts = explode('|', $id);
        } elseif (preg_match_all('/(?:\|\||[^\|])++/', $id, $matches)) {
            $parts = $matches[0];
        }

        return $parts;
    }
}
