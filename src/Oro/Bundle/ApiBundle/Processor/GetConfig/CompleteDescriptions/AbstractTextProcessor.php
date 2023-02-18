<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Component\ChainProcessor\AbstractMatcher;

/**
 * The base class for processors that helps to process expression depended placeholders in a text.
 */
class AbstractTextProcessor
{
    protected function processText(
        string $text,
        string $startTag,
        string $endTag,
        callable $valueMatcher
    ): string {
        $offset = 0;
        $startLength = \strlen($startTag);
        $endLength = \strlen($endTag);
        while (false !== ($startOpenPos = strpos($text, $startTag, $offset))) {
            $startClosePos = strpos($text, '}', $startOpenPos + $startLength);
            if (false === $startClosePos) {
                break;
            }
            $expression = substr(
                $text,
                $startOpenPos + $startLength,
                $startClosePos - $startOpenPos - $startLength
            );
            if (!$expression) {
                break;
            }
            $endClosePos = strpos($text, $endTag, $startClosePos + 1);
            if (false === $endClosePos) {
                break;
            }

            $body = '';
            if ($this->matchExpression($expression, $valueMatcher)) {
                $body = substr($text, $startClosePos + 1, $endClosePos - $startClosePos - 1);
            }

            $text = substr_replace($text, $body, $startOpenPos, ($endClosePos + $endLength) - $startOpenPos);
        }

        return $text;
    }

    private function matchExpression(string $expression, callable $valueMatcher): bool
    {
        if (strpos($expression, AbstractMatcher::OPERATOR_AND)) {
            $items = explode(AbstractMatcher::OPERATOR_AND, $expression);
            foreach ($items as $item) {
                if (!$this->matchItem($item, $valueMatcher)) {
                    return false;
                }
            }

            return true;
        }

        if (strpos($expression, AbstractMatcher::OPERATOR_OR)) {
            $items = explode(AbstractMatcher::OPERATOR_OR, $expression);
            foreach ($items as $item) {
                if ($this->matchItem($item, $valueMatcher)) {
                    return true;
                }
            }

            return false;
        }

        return $this->matchItem($expression, $valueMatcher);
    }

    private function matchItem(string $expression, callable $valueMatcher): bool
    {
        if (str_starts_with($expression, AbstractMatcher::OPERATOR_NOT)) {
            return !$valueMatcher(substr($expression, 1));
        }

        return $valueMatcher($expression);
    }
}
