<?php

namespace Oro\Bundle\EntityBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds UniqueEntity validation constraint to the entity validation metadata
 * if the entity has configured unique keys.
 */
class UniqueEntityExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
        private readonly ConfigProvider $entityConfigProvider,
        private readonly ConfigProvider $extendConfigProvider,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (empty($options['data_class'])) {
            return;
        }

        $className = $options['data_class'];
        if (!$this->doctrineHelper->isManageableEntity($className)) {
            return;
        }
        if (!$this->extendConfigProvider->hasConfig($className)) {
            return;
        }

        $uniqueKeys = $this->extendConfigProvider->getConfig($className)->get('unique_key');
        if (!$uniqueKeys) {
            return;
        }

        $validatorMetadata = $this->validator->getMetadataFor($className);
        foreach ($uniqueKeys['keys'] as $uniqueKey) {
            $fields = $uniqueKey['key'];
            $labels = array_map(
                function ($fieldName) use ($className) {
                    $fieldLabel = $this->entityConfigProvider->getConfig($className, $fieldName)->get('label');

                    return $fieldLabel ? $this->translator->trans($fieldLabel) : $fieldName;
                },
                $fields
            );
            $validatorMetadata->addConstraint(new UniqueEntity(
                fields: $fields,
                message: $this->translator->trans(
                    'oro.entity.validation.unique_field',
                    ['%count%' => \count($fields), '%field%' => implode(', ', $labels)]
                ),
                errorPath: ''
            ));
        }
    }
}
