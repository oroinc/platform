<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_11;

use Psr\Log\LoggerInterface;

use Exception;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class FixEmailTemplates extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Fix email templates with wrong calendar_date_range calls';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        // find duplicated calendars
        $sql = "SELECT * FROM oro_email_template
            WHERE content LIKE '%calendar_date_range%' ORDER BY id";
        $this->logQuery($logger, $sql);
        $templates = $this->connection->fetchAll($sql);

        try {
            $this->connection->beginTransaction();
            foreach ($templates as $template) {
                $pattern = <<<EOF
/(calendar_date_range\()([\w\d\.,]{0,}) ([\w\d\.,]{0,}) ([\w\d\.,]{0,}) ([\'\w\s\d\.,]{0,}\'),/
EOF;
                $replacement = '$1$2$3$4';
                $content = preg_replace($pattern, $replacement, $template['content']);
                $this->connection->update(
                    'oro_email_template',
                    ['content' => $content],
                    ['id' => $template['id']]
                );
            }
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }
}
