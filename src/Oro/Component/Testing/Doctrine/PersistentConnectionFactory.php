<?php
namespace Oro\Component\Testing\Doctrine;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;

class PersistentConnectionFactory extends ConnectionFactory
{
    /**
     * {@inheritdoc}
     */
    public function createConnection(
        array $params,
        Configuration $config = null,
        EventManager $eventManager = null,
        array $mappingTypes = array()
    ) {
        if (isset($params['wrapperClass'])) {
            throw new \LogicException('The wrapper class has already been defined. We cannot overwrite it.');
        }

        $params['wrapperClass'] = 'Oro\Component\Testing\Doctrine\PersistentConnection';

        return parent::createConnection($params, $config, $eventManager, $mappingTypes);
    }
}
