<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\Interval;
use Symfony\Component\Translation\MessageSelector as SymfonyMessageSelector;
use Symfony\Component\Translation\PluralizationRules;

/**
 * This message selector is an extension of Symfony`s message selector, but with error logging instead of
 * throwing an exceptions
 */
class MessageSelector extends SymfonyMessageSelector
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function choose($message, $number, $locale)
    {
        $parts = [];
        if (preg_match('/^\|++$/', $message)) {
            $parts = explode('|', $message);
        } elseif (preg_match_all('/(?:\|\||[^\|])++/', $message, $matches)) {
            $parts = $matches[0];
        }

        $explicitRules = [];
        $standardRules = [];
        foreach ($parts as $part) {
            $part = trim(str_replace('||', '|', $part));

            if (preg_match(
                '/^(?P<interval>' . Interval::getIntervalRegexp() . ')\s*(?P<message>.*?)$/xs',
                $part,
                $matches
            )) {
                $explicitRules[$matches['interval']] = $matches['message'];
            } elseif (preg_match('/^\w+\:\s*(.*?)$/', $part, $matches)) {
                $standardRules[] = $matches[1];
            } else {
                $standardRules[] = $part;
            }
        }

        // try to match an explicit rule, then fallback to the standard ones
        foreach ($explicitRules as $interval => $m) {
            try {
                if (Interval::test($number, $interval)) {
                    return $m;
                }
            } catch (InvalidArgumentException $e) {
                $this->logger->warning($e->getMessage());
            }
        }

        $position = PluralizationRules::get($number, $locale);

        if (!isset($standardRules[$position])) {
            // when there's exactly one rule given, and that rule is a standard
            // rule, use this rule
            if (1 === count($parts) && isset($standardRules[0])) {
                return $standardRules[0];
            }

            // when we have not enough translations for all plural forms - fallback to previous plural form
            $this->logger->warning(
                'Unable to choose a translation for "{message}" with locale "{locale}" for value "{number}". ' .
                'Double check that this translation has the correct plural options ' .
                '(e.g. "There is one apple|There are %%count%% apples").',
                ['message' => $message, 'locale' => $locale, 'number' => $number]
            );

            do {
                $position--;
                if (isset($standardRules[$position])) {
                    return $standardRules[$position];
                }
            } while ($position > 0);

            return $explicitRules ? end($explicitRules) : $message;
        }

        return $standardRules[$position];
    }
}
