<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Metadata\ClassMetadata;
use Metadata\PropertyMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class GetEntityAuditMetadataService
{
    /**
     * @var ConfigProvider
     */
    private $auditConfigProvider;

    /**
     * @param ConfigProvider $auditConfigProvider
     */
    public function __construct(ConfigProvider $auditConfigProvider)
    {
        $this->auditConfigProvider = $auditConfigProvider;
    }

    /**
     * @param string $entityClass
     *
     * @return ClassMetadata|null
     */
    public function getMetadata($entityClass)
    {
        $entityClass = ClassUtils::getRealClass($entityClass);

        if (false == $this->auditConfigProvider->hasConfig($entityClass)) {
            return null;
        }

        if (false == $this->auditConfigProvider->getConfig($entityClass)->is('auditable')) {
            return null;
        }

        $classMetadata = new ClassMetadata($entityClass);
        foreach ($classMetadata->reflection->getProperties() as $rp) {
            $fieldName = $rp->getName();

            if (false == $this->auditConfigProvider->hasConfig($entityClass, $fieldName)) {
                continue;
            }

            $fieldConfig = $this->auditConfigProvider->getConfig($entityClass, $fieldName);
            if (false == $fieldConfig->is('auditable')) {
                continue;
            }
            
            $propertyMetadata = new PropertyMetadata($entityClass, $rp->getName());
            $classMetadata->addPropertyMetadata($propertyMetadata);
        }

        return $classMetadata;
    }
}
