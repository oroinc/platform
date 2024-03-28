<?php

namespace Oro\Bundle\SanitizeBundle\Tools;

use Doctrine\DBAL\Connection;

/**
 * Checks the given SQL queries with the DB driver.
 */
class SqlSyntaxValidator
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @return string[]
     */
    public function validate(array $sqls): array
    {
        $errors = [];

        foreach ($sqls as $index => $sql) {
            $sql = trim($sql, " \n\r\t\v\x00;");
            $closing = !$this->isSqlPurelyComment($sql) ? ';' : '';

            try {
                $this->connection->executeStatement(
                    'DO $TEST$ BEGIN RETURN;' . PHP_EOL . $sql . PHP_EOL . $closing . 'END; $TEST$;'
                );
            } catch (\Throwable $e) {
                $errors[$index] = str_replace(
                    ['DO $TEST$ BEGIN RETURN;' . PHP_EOL, PHP_EOL . $closing . 'END; $TEST$;'],
                    ['', ''],
                    $this->fixLineNumberInMessage($e->getMessage())
                );
            }
        }

        return $errors;
    }

    public function isSqlPurelyComment(string $sql): bool
    {
        $sql = preg_replace('/(\\/\\*)(.*?)(\\*\\/)/', '', $sql);
        $sql = preg_replace('/--(.*?)(\\n|\\r\\n|\\r|$)/', '', $sql);

        return !strlen(trim($sql));
    }

    private function fixLineNumberInMessage(string $message): string
    {
        $message = str_replace(["\n", "\r\n", "\r"], PHP_EOL, $message);
        $fixedMessage = '';
        foreach (explode(PHP_EOL, $message) as $messageLine) {
            $fixedMessage .= preg_replace_callback('/LINE\\s+(\\d+)/', function (array $matches) {
                return 'LINE ' . ((int) $matches[1] - 1);
            }, $messageLine) . PHP_EOL;
        }

        return $fixedMessage;
    }
}
