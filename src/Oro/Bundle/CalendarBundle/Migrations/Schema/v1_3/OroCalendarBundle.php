<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCalendarBundle implements
    Migration,
    OrderedMigrationInterface,
    DatabasePlatformAwareInterface
{
    /** @var AbstractPlatform */
    protected $platform;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // fill createdAt and updatedAt
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_calendar_event SET created_at = :date, updated_at = :date',
                ['date' => new \DateTime('now', new \DateTimeZone('UTC'))],
                ['date' => Type::DATETIME]
            )
        );

        // copy title to description if needed
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                sprintf(
                    'UPDATE oro_calendar_event SET description = title WHERE %s > 255 OR title LIKE :new_line',
                    $this->platform->getLengthExpression('title')
                ),
                ['new_line' => '%\n%'],
                ['new_line' => Type::STRING]
            )
        );
        // trim title
        $locateExpr = $this->platform->getLocateExpression('title', ':lf');
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                sprintf(
                    'UPDATE oro_calendar_event SET title = %s WHERE %s > 0',
                    $this->platform->getSubstringExpression(
                        sprintf(
                            'REPLACE(%s, :cr, :empty)',
                            $this->platform->getSubstringExpression('title', 1, $locateExpr)
                        ),
                        1,
                        255
                    ),
                    $locateExpr
                ),
                ['lf' => '\n', 'cr' => '\r', 'empty' => ''],
                ['lf' => Type::STRING, 'cr' => Type::STRING, 'empty' => Type::STRING]
            )
        );
        $queries->addPreQuery(
            sprintf(
                'UPDATE oro_calendar_event SET title = %s WHERE %s > 255',
                $this->platform->getSubstringExpression('title', 1, 255),
                $this->platform->getLengthExpression('title')
            )
        );

        $table = $schema->getTable('oro_calendar_event');
        $table->getColumn('title')->setType(Type::getType(Type::STRING))->setOptions(['length' => 255]);
        $table->getColumn('created_at')->setOptions(['notnull' => true]);
        $table->getColumn('updated_at')->setOptions(['notnull' => true]);
        $this->enableDataAudit($table);
    }

    /**
     * @param Table $table
     */
    protected function enableDataAudit(Table $table)
    {
        $table->addOption(OroOptions::KEY, ['dataaudit' => ['auditable' => true]]);
        $table->getColumn('title')
            ->setOptions([OroOptions::KEY => ['dataaudit' => ['auditable' => true]]]);
        $table->getColumn('description')
            ->setOptions([OroOptions::KEY => ['dataaudit' => ['auditable' => true]]]);
        $table->getColumn('start_at')
            ->setOptions(
                [
                    OroOptions::KEY => [
                        ExtendOptionsManager::FIELD_NAME_OPTION => 'start',
                        'dataaudit'                             => ['auditable' => true]
                    ]
                ]
            );
        $table->getColumn('end_at')
            ->setOptions(
                [
                    OroOptions::KEY => [
                        ExtendOptionsManager::FIELD_NAME_OPTION => 'end',
                        'dataaudit'                             => ['auditable' => true]
                    ]
                ]
            );
        $table->getColumn('calendar_id')
            ->setOptions(
                [
                    OroOptions::KEY => [
                        ExtendOptionsManager::FIELD_NAME_OPTION => 'calendar',
                        'dataaudit'                             => ['auditable' => true]
                    ]
                ]
            );
        $table->getColumn('all_day')
            ->setOptions(
                [
                    OroOptions::KEY => [
                        ExtendOptionsManager::FIELD_NAME_OPTION => 'allDay',
                        'dataaudit'                             => ['auditable' => true]
                    ]
                ]
            );
    }
}
