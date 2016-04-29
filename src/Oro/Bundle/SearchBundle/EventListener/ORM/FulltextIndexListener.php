<?php

namespace Oro\Bundle\SearchBundle\EventListener\ORM;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use Oro\Bundle\SearchBundle\Entity\IndexText;

class FulltextIndexListener
{
    /**
     * @var string
     */
    protected $databaseDriver;

    /**
     * @param string $databaseDriver
     */
    public function __construct($databaseDriver)
    {
        $this->databaseDriver = $databaseDriver;
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

        if ($classMetadata->getTableName() !== IndexText::TABLE_NAME) {
            return;
        }

        $classMetadata->table['options']['engine'] = PdoMysql::ENGINE_MYISAM;
        $classMetadata->table['indexes']['value'] = ['columns' => ['value'], 'flags' => ['fulltext']];
    }
}
