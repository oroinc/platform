<?php

namespace Oro\Bundle\CalendarBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class OroCalendarBundle implements
    Migration,
    OrderedMigrationInterface,
    DatabasePlatformAwareInterface,
    NameGeneratorAwareInterface,
    ExtendExtensionAwareInterface,
    ActivityExtensionAwareInterface
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
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
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

        $this->activityExtension->addActivityAssociation($schema, 'oro_calendar_event', 'oro_user');
        $queries->addPostQuery($this->getFillUserActivityQuery());
    }

    /**
     * @return string
     */
    protected function getFillUserActivityQuery()
    {
        $sql = 'INSERT INTO %s (calendarevent_id, user_id)'
            . ' SELECT ce.id, c.user_owner_id'
            . ' FROM oro_calendar_event ce INNER JOIN oro_calendar c ON c.id = ce.calendar_id'
            . ' WHERE c.user_owner_id IS NOT NULL';

        return sprintf($sql, $this->getAssociationTableName('oro_user'));
    }

    /**
     * @param string $targetTableName
     *
     * @return string
     */
    protected function getAssociationTableName($targetTableName)
    {
        $sourceClassName = $this->extendExtension->getEntityClassByTableName('oro_calendar_event');
        $targetClassName = $this->extendExtension->getEntityClassByTableName($targetTableName);

        $associationName = ExtendHelper::buildAssociationName(
            $targetClassName,
            ActivityScope::ASSOCIATION_KIND
        );

        return $this->nameGenerator->generateManyToManyJoinTableName(
            $sourceClassName,
            $associationName,
            $targetClassName
        );
    }
}
