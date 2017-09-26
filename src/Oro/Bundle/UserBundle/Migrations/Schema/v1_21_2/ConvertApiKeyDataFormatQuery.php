<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_21_2;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\SecurityBundle\Encoder\RepetitiveCrypter;

/**
 * Converts data of api_key column of oro_user_api table to coded format.
 */
class ConvertApiKeyDataFormatQuery extends ParametrizedSqlMigrationQuery
{
    /** @var RepetitiveCrypter */
    protected $crypter;

    /**
     * @param RepetitiveCrypter $crypter
     */
    public function __construct(RepetitiveCrypter $crypter)
    {
        $this->crypter = $crypter;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $keys = $this->connection->createQueryBuilder()
            ->select('k.id, k.api_key')
            ->from('oro_user_api', 'k')
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($keys as $key) {
            $this->addSql(
                'UPDATE oro_user_api SET api_key = :api_key WHERE id = :id',
                [
                    'id'      => $key['id'],
                    'api_key' => $this->crypter->encryptData($key['api_key'])
                ],
                [
                    'id'      => Type::INTEGER,
                    'api_key' => Type::STRING
                ]
            );
        }

        parent::processQueries($logger, $dryRun);
    }
}
