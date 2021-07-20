<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\Query;

/**
 * Provides a set of static methods to work with HINT_FORCE_PARTIAL_LOAD query hint in entity config.
 */
final class ForcePartialLoadHintUtil
{
    /**
     * Checks whether using of Doctrine HINT_FORCE_PARTIAL_LOAD query hint is allowed.
     */
    public static function isForcePartialLoadHintEnabled(EntityConfig $config): bool
    {
        $hints = $config->getHints();
        if (!$hints) {
            return true;
        }

        $result = true;
        foreach ($hints as $hint) {
            if (\is_array($hint)
                && false === ($hint['value'] ?? false)
                && self::isForcePartialLoadHint($hint['name'])
            ) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Disallows using of Doctrine HINT_FORCE_PARTIAL_LOAD query hint.
     */
    public static function disableForcePartialLoadHint(EntityConfig $config): void
    {
        $hints = $config->getHints();
        $done = false;
        if ($hints) {
            foreach ($hints as $hint) {
                if (\is_array($hint)) {
                    if (self::isForcePartialLoadHint($hint['name'])) {
                        if (false !== ($hint['value'] ?? false)) {
                            $config->removeHint($hint['name']);
                        } else {
                            $done = true;
                        }
                        break;
                    }
                } elseif (\is_string($hint) && self::isForcePartialLoadHint($hint)) {
                    $config->removeHint($hint);
                    break;
                }
            }
        }
        if (!$done) {
            $config->addHint(Query::HINT_FORCE_PARTIAL_LOAD, false);
        }
    }

    private static function isForcePartialLoadHint(string $hintName): bool
    {
        return
            'HINT_FORCE_PARTIAL_LOAD' === $hintName
            || Query::HINT_FORCE_PARTIAL_LOAD === $hintName;
    }
}
