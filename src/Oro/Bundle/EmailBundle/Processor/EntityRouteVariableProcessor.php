<?php

namespace Oro\Bundle\EmailBundle\Processor;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Routing\RouterInterface;

class EntityRouteVariableProcessor implements VariableProcessorInterface
{
    /** @var RouterInterface */
    protected $router;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityConfigManager */
    protected $entityConfigManager;

    /** @var string */
    protected $basePath;

    public function __construct(
        RouterInterface $router,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        EntityConfigManager $entityConfigManager
    ) {
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->entityConfigManager = $entityConfigManager;
    }

    /**
     * {@inheritDoc}
     */
    public function process($variable, array $definition, array $data = [])
    {
        if (!$this->isValid($variable, $definition, $data)) {
            return sprintf('{{ \'%s\' }}', $variable);
        }

        $params = [];

        if (!preg_match('/^.*(_index|_create)$/', $definition['route'])) {
            $params = [
                'id' => $this->doctrineHelper->getSingleEntityIdentifier($data['entity'], false),
            ];
        }

        return sprintf('{{ \'%s\' }}', $this->getBasePath() . $this->router->generate($definition['route'], $params));
    }

    /**
     * @param string $variable
     *
     * @return bool
     */
    protected function supports($variable)
    {
        return in_array($variable, [
            'entity.url.index',
            'entity.url.view',
            'entity.url.create',
            'entity.url.update',
            'entity.url.delete',
        ], true);
    }

    /**
     * @param $variable
     * @param array $definition
     * @param array $data
     *
     * @return bool
     */
    protected function isValid($variable, array $definition, array $data = [])
    {
        if (!$this->supports($variable)) {
            return false;
        }

        if (!isset($definition['route']) || !$this->router->getRouteCollection()->get($definition['route'])) {
            return false;
        }

        if (!isset($data['entity']) || !$this->doctrineHelper->isManageableEntity($data['entity'])) {
            return false;
        }


        $entityClass = ClassUtils::getRealClass(get_class($data['entity']));
        $extendConfigProvider = $this->entityConfigManager->getProvider('extend');
        if (!$extendConfigProvider->hasConfig($entityClass)
            || !ExtendHelper::isEntityAccessible($extendConfigProvider->getConfig($entityClass))
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    private function getBasePath()
    {
        if (!isset($this->basePath)) {
            $this->basePath = $this->configManager->get('oro_ui.application_url');
        }

        return $this->basePath;
    }
}
