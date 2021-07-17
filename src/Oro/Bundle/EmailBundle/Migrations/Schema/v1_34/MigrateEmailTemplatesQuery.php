<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_34;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Copy email templates translations to templates localizations
 */
class MigrateEmailTemplatesQuery extends ParametrizedMigrationQuery
{
    /** @var string Check content on wysiwyg empty formatting */
    private const EMPTY_REGEX = '#^(\r*\n*)*'
        . '\<!DOCTYPE html\>(\r*\n*)*'
        . '\<html\>(\r*\n*)*'
        . '\<head\>(\r*\n*)*\</head\>(\r*\n*)*'
        . '\<body\>(\r*\n*)*\</body\>(\r*\n*)*'
        . '\</html\>(\r*\n*)*$#';

    private const BATCH_SIZE = 500;

    /** @var Schema */
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger, false);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function doExecute(LoggerInterface $logger, bool $dryRun): void
    {
        $qb = $this->connection->createQueryBuilder()
            ->select(
                'loc.id as localization_id',
                'trans.object_id as template_id'
            )
            ->from('oro_email_template_translation', 'trans')
            ->groupBy('trans.object_id, trans.locale, loc.id')
            ->setFirstResult(0)
            ->setMaxResults(self::BATCH_SIZE);

        if ($this->schema->getTable('oro_localization')->hasColumn('language_id')) {
            $qb
                ->innerJoin('trans', 'oro_language', 'lang', 'lang.code = trans.locale')
                ->innerJoin('lang', 'oro_localization', 'loc', 'loc.language_id = lang.id');
        } else {
            $qb->innerJoin('trans', 'oro_localization', 'loc', 'loc.language_code = trans.locale');
        }

        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof MySqlPlatform) {
            $qb->addSelect(
                "GROUP_CONCAT(CASE WHEN trans.field = 'subject' THEN content ELSE null END) as subject",
                "GROUP_CONCAT(CASE WHEN trans.field = 'content' THEN content ELSE null END) as content"
            );
        } else {
            $qb->addSelect(
                "STRING_AGG(CASE WHEN trans.field = 'subject' THEN content ELSE null END, ',') as subject",
                "STRING_AGG(CASE WHEN trans.field = 'content' THEN content ELSE null END, ',') as content"
            );
        }

        $queryForLogger = 'INSERT INTO oro_email_template_localized'
            . ' (localization_id, template_id, subject, subject_fallback, content, content_fallback)'
            . ' VALUES (?, ?, ?, ?, ?, ?)';

        $types = [
            'integer',
            'integer',
            'string',
            'boolean',
            'string',
            'boolean',
        ];

        do {
            $this->logQuery($logger, $qb->getSQL());

            $stm = $qb->execute();

            foreach ($stm->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                if (preg_match(self::EMPTY_REGEX, $row['content'])) {
                    $row['content'] = null;
                }

                $data = [
                    'localization_id' => $row['localization_id'],
                    'template_id' => $row['template_id'],
                    'subject' => $row['subject'],
                    'subject_fallback' => $row['subject'] === null,
                    'content' => $row['content'],
                    'content_fallback' => $row['content'] === null
                ];

                $this->logQuery($logger, $queryForLogger, $data, $types);

                if (!$dryRun) {
                    $this->connection->insert('oro_email_template_localized', $data, $types);
                }
            }

            $qb->setFirstResult($qb->getFirstResult() + $qb->getMaxResults());
        } while ($stm->rowCount() > 0);
    }
}
