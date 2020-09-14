<?php

namespace Oro\Component\DoctrineUtils\DBAL;

use \PDO;

/**
 * Provide DB privileges for a connected user.
 */
class DbPrivilegesProvider
{
    /**
     * @param PDO $pdo
     * @param string $dbName
     * @return array
     */
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
            // Drop temporary table
            $pdo->exec('DROP TABLE oro_privileges_check');
            $granted[] = 'DROP';
        } catch (\Exception $e) {
            return [];
        }

        return $granted;
    }

    /**
     * @param PDO $pdo
     * @param string $dbName
     * @return array
     */
    public static function getMySqlGrantedPrivileges(PDO $pdo, string $dbName): array
    {
        $grantRows = $pdo->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);
        $grantedPrivileges = [];
        foreach ($grantRows as $grantRow) {
            preg_match_all('/GRANT\s+(.+?)\s+ON\s+(.+?)\sTO/', $grantRow, $grants);

            [$db, $host] = explode('.', $grants[2][0]);
            $db = trim($db, '`');

            if ($db === $dbName || $db === '*') {
                $privileges = array_map('trim', explode(',', $grants[1][0]));
                $grantedPrivileges[] = $privileges;
            }
        }

        $granted = array_merge(...$grantedPrivileges);
        array_unique($granted);

        return $granted;
    }
}
