<?php

namespace Oro\Bundle\SearchBundle\EventListener\ORM;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

class FulltextIndexListener
{
    /**
     * @var string
     */
    protected $databaseDriver;

    /**
     * @var string
     */
    protected $textIndexTableName;

    /**
     * @param string $databaseDriver
     * @param string $textIndexTableName
     */
    public function __construct($databaseDriver, $textIndexTableName)
    {
        $this->databaseDriver = $databaseDriver;
        $this->textIndexTableName = $textIndexTableName;
    }

    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        if ($this->databaseDriver !== DatabaseDriverInterface::DRIVER_MYSQL) {
            return;
        }

        /** @var ClassMetadataInfo $classMetadata */
        $classMetadata = $event->getClassMetadata();

        if ($classMetadata->getTableName() !== $this->textIndexTableName) {
            return;
        }

        $classMetadata->table['options']['engine'] = PdoMysql::ENGINE_MYISAM;
        $classMetadata->table['indexes']['value'] = ['columns' => ['value'], 'flags' => ['fulltext']];
    }
}
