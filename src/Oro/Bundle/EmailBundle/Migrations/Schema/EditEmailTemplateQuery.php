<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * This query is designed to modify content of existing email templates in database
 */
class EditEmailTemplateQuery extends ParametrizedMigrationQuery
{
    /** @var string */
    protected $templateName;

    /** @var string */
    protected $from;

    /** @var string */
    protected $to;

    /**
     * @param string $templateName
     * @param string $from
     * @param string $to
     */
    public function __construct($templateName, $from, $to)
    {
        $this->templateName = $templateName;
        $this->from = $from;
        $this->to = $to;
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
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $query = 'UPDATE oro_email_template SET content=REPLACE(content, :from, :to) WHERE name=:name';
        $params = ['from' => $this->from, 'to' => $this->to, 'name' => $this->templateName];
        $types = ['from' => 'text', 'to' => 'text', 'name' => 'string'];

        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }

        $query = 'UPDATE oro_email_template_translation 
          SET content=REPLACE(content, :from, :to)
          WHERE field=:field AND object_id = (SELECT id FROM oro_email_template WHERE name=:name LIMIT 1)';
        $params['field'] = 'content';
        $types['field'] = 'string';

        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $params, $types);
        }
    }
}
