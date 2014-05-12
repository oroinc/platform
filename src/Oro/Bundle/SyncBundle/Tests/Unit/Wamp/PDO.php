<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Wamp;

/**
 * Class Mock PDO
 *
 * @package Oro\Bundle\SyncBundle\Tests\Unit\Wamp
 */
class PDO extends \PDO
{
    protected $query;

    public function __construct($a, $b, $c)
    {
        $arbitrary = "nothing important here";
    }

    public function query($sql)
    {
        return 'some text';
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function prepare($statement, $options = null)
    {
        $this->query = $statement;
        return new PDOStatement();
    }
}
