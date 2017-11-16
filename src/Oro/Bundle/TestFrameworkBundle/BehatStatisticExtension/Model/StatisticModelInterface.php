<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model;

use Doctrine\DBAL\Schema\Schema;

interface StatisticModelInterface
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * @return boolean
     */
    public function isNew();

    /**
     * @return int duration in seconds
     */
    public function getDuration();

    /**
     * Prepare Model to the db representation
     *
     * @return array
     */
    public function toArray();

    /**
     * @param array $data
     * @return StatisticModelInterface
     */
    public static function fromArray(array $data);

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

    /**
     * @return string
     */
    public static function getIdField();
}
