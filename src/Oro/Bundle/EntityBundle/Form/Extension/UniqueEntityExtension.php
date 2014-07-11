<?php

namespace Oro\Bundle\EntityBundle\Form\Extension;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\ValidatorInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

class UniqueEntityExtension extends AbstractTypeExtension
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ConfigProviderInterface
     */
    protected $entityConfigProvider;

    /**
     * @param ValidatorInterface      $validator
     * @param TranslatorInterface     $translator
     * @param ConfigProviderInterface $entityConfigProvider
     */
    public function __construct(
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        ConfigProviderInterface $entityConfigProvider
    ) {
        $this->validator            = $validator;
        $this->translator           = $translator;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (empty($options['data_class'])) {
            return;
        }

        $className = $options['data_class'];

        if (!$this->entityConfigProvider->hasConfig($className)) {
            return;
        }

        $config = $this->entityConfigProvider->getConfig($className);

        if (!$config->has('unique_key')) {
            return;
        }

        $uniqueKeys = $config->get('unique_key', ['keys' => []]);

        /* @var \Symfony\Component\Validator\Mapping\ClassMetadata $validatorMetadata */
        $validatorMetadata = $this->validator->getMetadataFor($className);

        foreach ($uniqueKeys['keys'] as $uniqueKey) {
            $fields = $uniqueKey['key'];

            $labels = array_map(
                function ($fieldName) use ($className) {
                    $label = $this
                        ->entityConfigProvider
                        ->getConfig($className, $fieldName)
                        ->get('label');

                    return $this->translator->trans($label);
                },
                $fields
            );

            $constraint = new UniqueEntity(
                [
                    'fields'    => $fields,
                    'errorPath' => '',
                    'message'   => $this
                        ->translator
                        ->transChoice(
                            'oro.entity.validation.unique_field',
                            sizeof($fields),
                            ['%field%' => implode(', ', $labels)]
                        ),
                ]
            );

            $validatorMetadata->addConstraint($constraint);
        }
    }
}
