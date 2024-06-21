<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The default provider for additional associations for email notifications.
 */
class AdditionalEmailAssociationProvider implements AdditionalEmailAssociationProviderInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ConfigProvider */
    private $entityConfigProvider;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        ManagerRegistry $doctrine,
        ConfigProvider $entityConfigProvider,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctrine;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociations(string $entityClass): array
    {
        $associations = [];
        $metadata = $this->getEntityMetadata($entityClass);
        if (null !== $metadata) {
            foreach ($metadata->getAssociationMappings() as $fieldName => $mapping) {
                $associations[$fieldName] = [
                    'label'        => $this->getFieldLabel($entityClass, $fieldName),
                    'target_class' => $mapping['targetEntity']
                ];
            }
        }

        return $associations;
    }

    /**
     * {@inheritdoc}
     */
    public function isAssociationSupported($entity, string $associationName): bool
    {
        return null !== $this->getEntityMetadata(ClassUtils::getClass($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociationValue($entity, string $associationName)
    {
        if ($entity instanceof Proxy && !$entity->__isInitialized()) {
            $entity->__load();
        }

        return $this->getEntityMetadata(ClassUtils::getClass($entity))
            ->getFieldValue($entity, $associationName);
    }

    private function getEntityMetadata(string $entityClass): ?ClassMetadata
    {
        $em = $this->doctrine->getManagerForClass($entityClass);
        if (!$em instanceof EntityManagerInterface) {
            return null;
        }

        return $em->getClassMetadata($entityClass);
    }

    private function getFieldLabel(string $entityClass, string $fieldName): string
    {
        if (!$this->entityConfigProvider->hasConfig($entityClass, $fieldName)) {
            return $this->prettifyFieldName($fieldName);
        }

        return $this->translator->trans(
            (string) $this->entityConfigProvider->getConfig($entityClass, $fieldName)->get('label')
        );
    }

    private function prettifyFieldName(string $fieldName): string
    {
        $fieldLabel = ucfirst($fieldName);
        if (preg_match('/_[a-z0-9]{8}$/', $fieldLabel)) {
            $fieldLabel = preg_replace('/_[a-z0-9]{8}$/', '', $fieldLabel);
        }
        $fieldLabel = str_replace('_', ' ', $fieldLabel);
        $fieldLabel = preg_replace('/([a-z])([A-Z])/', '$1 $2', $fieldLabel);

        return $fieldLabel;
    }
}
