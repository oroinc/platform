<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model;

use Doctrine\DBAL\Schema\Schema;

interface StatisticModelInterface
{
    /**
     * Prepare Model to the db representation
     *
     * @return array
     */
    public function toArray();

    /**
     * @return boolean
     */
    public function isNew();

    /**
     * Describe schema for to CRUD model to persistent layer
     *
     * @param Schema $schema
     * @return void
     */
    public static function declareSchema(Schema $schema);

    /**
     * @return string
     */
    public static function getName();
}
