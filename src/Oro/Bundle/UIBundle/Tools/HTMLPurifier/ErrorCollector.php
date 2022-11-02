<?php

namespace Oro\Bundle\UIBundle\Tools\HTMLPurifier;

/**
 * Error collection class that enables HTMLPurifier to report HTML problems back to the user
 */
class ErrorCollector extends \HTMLPurifier_ErrorCollector
{
    public const LENGTH = 25;

    /**
     * @param string $value
     * @return Error[]
     */
    public function getErrorsList(string $value): array
    {
        $errorsList = [];
        foreach ($this->lines as $line => $column) {
            if ($column instanceof \HTMLPurifier_ErrorStruct) {
                $line = $line < 0 ? 0 : $line;
                $place = mb_substr($value, $line, self::LENGTH);
                $this->getErrors($errorsList, $column, $place);

                continue;
            }

            foreach ($column as $col => $struct) {
                $place = mb_substr($value, $col, self::LENGTH);
                $this->getErrors($errorsList, $struct, $place);
            }
        }

        return $errorsList;
    }

    protected function getErrors(array &$errorsList, \HTMLPurifier_ErrorStruct $struct, string $place): void
    {
        $stack = [$struct];
        $contextStack = [[]];
        while ($current = array_pop($stack)) {
            $context = array_pop($contextStack);
            foreach ($current->errors as [$severity, $message]) {
                $errorsList[] = new Error($message, $place);
            }
            foreach ($current->children as $children) {
                $context[] = $current;
                for ($i = count($children); $i > 0; $i--) {
                    $contextStack[] = $context;
                }
            }
            $stack = array_merge($stack, ...array_reverse($current->children, true));
        }
    }
}
