<?php

namespace Oro\Bundle\ApiBundle\Form\Guesser;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;

use Oro\Bundle\ApiBundle\Config\ConfigAccessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class InverseAssociationTypeGuesser implements FormTypeGuesserInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigAccessorInterface|null */
    protected $configAccessor;

    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param AssociationManager $associationManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AssociationManager $associationManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->associationManager = $associationManager;
    }

    /**
     * @param ConfigAccessorInterface|null $configAccessor
     */
    public function setConfigAccessor(ConfigAccessorInterface $configAccessor = null)
    {
        $this->configAccessor = $configAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        $config = $this->getConfigForClass($class);
        if (null !== $config) {
            $fieldConfig = $config->getField($property);
            if (null !== $fieldConfig) {
                $dataType = $fieldConfig->getDataType();
                if (DataType::isExtendedInverseAssociation($dataType)) {
                    $associationMetadata = new AssociationMetadata($property);
                    $associationMetadata->setTargetClassName($class);
                    $associationMetadata->setIsNullable(true);
                    $associationMetadata->setCollapsed($fieldConfig->isCollapsed());
                    list($associationSourceClass, $associationType, $associationKind)
                        = DataType::parseExtendedInverseAssociation($dataType);
                    $associationMetadata->setTargetClassName($associationSourceClass);
                    $associationMetadata->setAcceptableTargetClassNames([$associationSourceClass]);
                    $reverseType = ExtendHelper::getReverseRelationType(
                        $associationType
                    );
                    $targets = $this->getExtendedAssociationTargets(
                        $associationSourceClass,
                        $associationType,
                        $associationKind
                    );
                    $associationMetadata->set('association-field', $targets[$class]);
                    $associationMetadata->setAssociationType($reverseType);
                    $associationMetadata->setIsCollection((bool)$fieldConfig->isCollectionValuedAssociation());

                    return new TypeGuess(
                        'oro_api_entity',
                        ['metadata' => $associationMetadata],
                        TypeGuess::HIGH_CONFIDENCE
                    );
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessRequired($class, $property)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessMaxLength($class, $property)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function guessPattern($class, $property)
    {
        return null;
    }

    /**
     * @param string $class
     *
     * @return EntityDefinitionConfig|null
     */
    protected function getConfigForClass($class)
    {
        return null !== $this->configAccessor
            ? $this->configAccessor->getConfig($class)
            : null;
    }

    /**
     * @param string $entityClass
     * @param string $associationType
     * @param string $associationKind
     *
     * @return array [class name => field name, ...]
     */
    protected function getExtendedAssociationTargets($entityClass, $associationType, $associationKind)
    {
        $targets = $this->associationManager->getAssociationTargets(
            $entityClass,
            null,
            $associationType,
            $associationKind
        );

        return $targets;
    }
}
