<?php

namespace Oro\Bundle\NoteBundle\Tools;

use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGeneratorExtension;

class NoteExtendGenerator implements ExtendEntityGeneratorExtension
{
    const NOTE_CONFIG_SCOPE = 'note';

    /** @var ConfigProvider */
    protected $noteConfigProvider;

    public function __construct(ConfigManager $configManager)
    {
        $this->noteConfigProvider = $configManager->getProvider(self::NOTE_CONFIG_SCOPE);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType, array $schemas)
    {
        if ($actionType == ExtendEntityGeneratorExtension::ACTION_PRE_PROCESS) {
            $this->getEntityConfigWithNotesEnabled();
        }


        return true;
    }

    protected function getEntityConfigWithNotesEnabled()
    {
        $configs = $this->noteConfigProvider->getConfigs();

        //$withNotes = [];
        foreach ($configs as $config) {
            //
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preProcessEntityConfiguration(array &$schemas)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function generate(array &$schema, PhpClass $class)
    {
        // TODO: generate unidirectional relations to entities that use notes
    }
}
