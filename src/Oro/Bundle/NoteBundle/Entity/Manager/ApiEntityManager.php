<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\Model\EntityIdSoap;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager as BaseApiEntityManager;

class ApiEntityManager extends BaseApiEntityManager
{
    /** @var  ConfigManager $configManager */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    public function __construct($class, ObjectManager $om, ConfigManager $configManager)
    {
        parent::__construct($class, $om);

        $this->configManager = $configManager;
    }

    public function find($id)
    {
        $result = parent::find($id);

        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityId = null;

        /** @var FieldConfigId[] $fieldConfigIds */
        $fieldConfigIds = $extendConfigProvider->getIds($this->class);
        foreach ($fieldConfigIds as $fieldConfigId) {
            if ($fieldConfigId->getFieldType() === 'manyToOne') {
                $fieldExtendConfig = $extendConfigProvider->getConfigById($fieldConfigId);
                if (!$fieldExtendConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                    continue;
                }

                $value = $result->{Inflector::camelize('get_' . $fieldConfigId->getFieldName())}();
                if ($value) {
                    $entityId = new EntityIdSoap();
                    $entityId
                        ->setEntity($fieldExtendConfig->get('target_entity'))
                        ->setId($value->getId());

                    $result->entityId = $entityId;
                    break;
                }
            }
        }

        if ($entityId === null) {
            throw new \LogicException('Note entity cannot be unassigned.');
        }

        return $result;
    }
}
