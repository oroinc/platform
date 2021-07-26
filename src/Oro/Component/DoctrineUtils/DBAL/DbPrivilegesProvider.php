<?php

namespace Oro\Component\DoctrineUtils\DBAL;

use PDO;

/**
 * Provide DB privileges for a connected user.
 */
class DbPrivilegesProvider
{
    public static function getPostgresGrantedPrivileges(PDO $pdo, string $dbName): array
    {
        try {
            // Check create permission.
            // Create table to fetch granted permissions
            $pdo->exec('CREATE TABLE oro_privileges_check(id INT)');
            $granted = ['CREATE'];
        } catch (\Exception $e) {
            return [];
        }

        $stmt = $pdo->prepare(
            "SELECT privilege_type 
            FROM information_schema.role_table_grants 
            WHERE table_catalog = :tableSchema
            AND table_name = :tableName"
        );
        $stmt->bindValue('tableSchema', $dbName, PDO::PARAM_STR);
        $stmt->bindValue('tableName', 'oro_privileges_check', PDO::PARAM_STR);
        $stmt->execute();

        $granted = array_merge(
            $stmt->fetchAll(PDO::FETCH_COLUMN),
            $granted
        );

        try {
            // Check TEMPORARY permission.
            // CREATE TEMP TABLE to fetch granted permissions
            $pdo->exec(
                'CREATE TEMP TABLE IF NOT EXISTS oro_privileges_check_tmp AS TABLE oro_privileges_check WITH NO DATA'
            );
            $granted[] = 'TEMPORARY';
        } catch (\Exception $e) {
            return $granted;
        }

        try {
            // Drop temporary table
            $pdo->exec('DROP TABLE oro_privileges_check');
            $pdo->exec('DROP TABLE IF EXISTS oro_privileges_check_tmp');
            $granted[] = 'DROP';
        } catch (\Exception $e) {
        }

        return $granted;
    }

    public static function getMySqlGrantedPrivileges(PDO $pdo, string $dbName): array
    {
        $grantRows = $pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
        $grantedPrivileges = [];
        foreach ($grantRows as $grantRow) {
            preg_match_all('/GRANT\s+(.+?)\s+ON\s+(.+?)\sTO/', $grantRow, $grants);

            $db = null;
            $hostStr = $grants[2][0] ?? '';
            if (str_contains($hostStr, '.')) {
                [$db, $host] = explode('.', $grants[2][0]);
            }
            $db = trim($db, '`');

            // MySQL wildcard support for DB name
            $dbRegExp = str_replace(['*', '%'], '.*?', $db);
            $dbRegExp = preg_replace('/(?<!\\\)_/', '.', $dbRegExp);
            $dbRegExp = str_replace('\_', '_', $dbRegExp);

            if ($db === $dbName || $db === '*' || preg_match('/^' . $dbRegExp . '$/', $dbName)) {
                $privileges = array_map('trim', explode(',', $grants[1][0]));
                $grantedPrivileges[] = $privileges;
            }
        }

        $granted = array_merge(...$grantedPrivileges);
        array_unique($granted);

        return $granted;
    }
}
