<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This listener changes the length of some fields for workflow entities to avoid
 * "Specified key was too long; max key length is 3072 bytes" error
 * in case if "utf8mb4" charset is used for MySQL database.
 * The reason why this listener was added is to allow install an application with "utf8mb4" charset,
 * but avoid to change the database schema during upgrade to a patch release.
 */
class FieldLengthListener
{
    /**
     * @param LoadClassMetadataEventArgs $event
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        if (self::isFieldLengthCorrectionRequired($event->getEntityManager()->getConnection())) {
            /** @var ClassMetadata $classMetadata */
            $classMetadata = $event->getClassMetadata();
            $className = $classMetadata->getName();
            switch ($className) {
                case 'Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger':
                    $classMetadata->fieldMappings['field']['length'] = 150;
                    break;
                case 'Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger':
                    $classMetadata->fieldMappings['field']['length'] = 150;
                    break;
                case 'Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction':
                    $classMetadata->fieldMappings['field']['length'] = 150;
                    $classMetadata->fieldMappings['mode']['length'] = 8;
                    break;
            }
        }
    }

    /**
     * @param Connection $connection
     *
     * @return bool
     */
    public static function isFieldLengthCorrectionRequired(Connection $connection)
    {
        $result = false;
        if ($connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $params = $connection->getParams();
            if (isset($params['defaultTableOptions']['charset'])
                && 'utf8mb4' === $params['defaultTableOptions']['charset']
            ) {
                $result = true;
            }
        }

        return $result;
    }
}
