<?php

namespace Oro\Bundle\NoteBundle\Tools;

use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\BaseGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGeneratorExtension;

class NoteExtendGenerator extends BaseGenerator implements ExtendEntityGeneratorExtension
{
    const NOTE_CONFIG_SCOPE = 'note';

    /** @var ConfigProvider */
    protected $noteConfigProvider;

    public function __construct(ConfigManager $configManager)
    {
        parent::__construct();

        $this->noteConfigProvider = $configManager->getProvider(self::NOTE_CONFIG_SCOPE);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionType, array $schemas)
    {
        return true;
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
