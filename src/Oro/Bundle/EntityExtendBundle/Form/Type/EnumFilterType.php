<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractChoiceType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;

class EnumFilterType extends AbstractChoiceType
{
    const NAME = 'oro_enum_filter';

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param TranslatorInterface $translator
     * @param ManagerRegistry     $doctrine
     */
    public function __construct(TranslatorInterface $translator, ManagerRegistry $doctrine)
    {
        parent::__construct($translator);
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaultFieldOptions = [
            'multiple' => true
        ];

        $resolver->setDefaults(
            [
                // either enum_code or class must be specified
                'enum_code'     => null,
                'class'         => null,
                'field_options' => $defaultFieldOptions
            ]
        );
        $resolver->setNormalizers(
            [
                'class'         => function (Options $options, $value) {
                    return $value !== null
                        ? $value
                        : ExtendHelper::buildEnumValueClassName($options['enum_code']);
                },
                // this normalizer allows to add/override field_options options outside
                'field_options' => function (Options $options, $value) use (&$defaultFieldOptions) {
                    $value['choices'] = $options['class'] !== null
                        ? $this->getChoices($options['class'], $options['null_value'])
                        : [];

                    return array_merge($defaultFieldOptions, $value);
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceFilterType::NAME;
    }

    /**
     * @param string      $enumValueClassName
     * @param string|null $nullValue
     *
     * @return array
     */
    protected function getChoices($enumValueClassName, $nullValue)
    {
        $choices = [];

        if (!empty($nullValue)) {
            $choices[$nullValue] = $this->translator->trans('oro.entity_extend.datagrid.enum.filter.empty');
        }

        if (!empty($enumValueClassName)) {
            /** @var EntityRepository $repo */
            $repo = $this->doctrine->getRepository($enumValueClassName);
            /** @var AbstractEnumValue[] $values */
            $values = $repo->createQueryBuilder('o')
                ->orderBy('o.priority')
                ->getQuery()
                ->getResult();

            foreach ($values as $value) {
                $choices[$value->getId()] = $value->getName();
            }
        }

        return $choices;
    }
}
