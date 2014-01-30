<?php

namespace Oro\Bundle\DataAuditBundle\Twig;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class AuditableExtension extends \Twig_Extension
{
    /**
     * @var ConfigProvider
     */
    protected $auditConfigProvider;

    /**
     * @param ConfigProvider $auditConfigProvider
     */
    public function __construct(ConfigProvider $auditConfigProvider)
    {
        $this->auditConfigProvider = $auditConfigProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_auditable';
    }

    public function getTests()
    {
        return array(
            new \Twig_SimpleTest('auditable', array($this, 'isAuditable'))
        );
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function isAuditable($entity)
    {
        if (is_array($entity)) {
            return false;
        }

        if (!is_object($entity)) {
            $className = str_replace('_', '\\', $entity);
        } else {
            $className = ClassUtils::getClass($entity);
        }

        if ($entity instanceof EntityConfigModel) {
            $className = $entity->getClassName();
        }

        return $this->auditConfigProvider->hasConfig($className)
            && $this->auditConfigProvider->getConfig($className)->is('auditable');
    }
}
