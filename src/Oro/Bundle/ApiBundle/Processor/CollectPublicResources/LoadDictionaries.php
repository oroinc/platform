<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectPublicResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\PublicResource;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Collects resources for all entities marked as a dictionary.
 */
class LoadDictionaries implements ProcessorInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectPublicResourcesContext $context */

        $resources = $context->getResult();
        $configs   = $this->configManager->getConfigs('grouping', null, true);
        foreach ($configs as $config) {
            $groups = $config->get('groups');
            if (!empty($groups)
                && in_array(GroupingScope::GROUP_DICTIONARY, $groups, true)
                && ExtendHelper::isEntityAccessible(
                    $this->configManager->getEntityConfig('extend', $config->getId()->getClassName())
                )
            ) {
                $resources->add(new PublicResource($config->getId()->getClassName()));
            }
        }
    }
}
