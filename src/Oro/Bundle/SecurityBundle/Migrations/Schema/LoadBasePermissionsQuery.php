<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class LoadBasePermissionsQuery extends ParametrizedSqlMigrationQuery
{
    /** @var array */
    protected $permissions = [
        'VIEW',
        'CREATE',
        'EDIT',
        'DELETE',
        'ASSIGN'
    ];
    
    /**
     * {@inheritdoc}
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $query = 'INSERT INTO oro_security_permission (name, label, is_apply_to_all, group_names, description) ' .
            'VALUES (:name, :label, :is_apply_to_all, :group_names, :description)';

        $types = [
            'name' => Type::STRING,
            'label' => Type::STRING,
            'is_apply_to_all' => Type::BOOLEAN,
            'group_names' => Type::TARRAY,
            'description' => Type::STRING
        ];

        foreach ($this->permissions as $permission) {
            $this->addSql(
                $query,
                [
                    'name' => $permission,
                    'label' => $permission,
                    'is_apply_to_all' => true,
                    'group_names' => ['default'],
                    'description' => null
                ],
                $types
            );
        }

        parent::processQueries($logger, $dryRun);
    }
}
