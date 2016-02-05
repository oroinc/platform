<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectPublicResources;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Request\PublicResource;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Collects resources for all entities marked as a public enums. Both "enum" and "multiEnum" types are processed.
 */
class LoadPublicEnums implements ProcessorInterface
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
        $configs   = $this->configManager->getConfigs('enum', null, true);
        foreach ($configs as $config) {
            $enumCode = $config->get('code');
            if (!empty($enumCode)
                && $config->is('public')
                && ExtendHelper::isEntityAccessible(
                    $this->configManager->getEntityConfig('extend', $config->getId()->getClassName())
                )
            ) {
                $resources->add(new PublicResource($config->getId()->getClassName()));
            }
        }
    }
}
