<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetEntityJquerySelect2HiddenType;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;

class WidgetUserSelectType extends WidgetEntityJquerySelect2HiddenType
{
    const NAME = 'oro_type_widget_user_select';

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'widget_owner_users',
                'configs'            => [
                    'multiple'                => true,
                    'width'                   => '400px',
                    'placeholder'             => 'oro.user.form.choose_user',
                    'allowClear'              => true,
                    'result_template_twig'    => 'OroUserBundle:User:Autocomplete/Widget/result.html.twig',
                    'selection_template_twig' => 'OroUserBundle:User:Autocomplete/Widget/selection.html.twig',
                ]
            ]
        );
    }

    /**
     * @param string $entityClass
     * @param array  $ids
     *
     * @return array
     */
    protected function getEntitiesByIdentifiers($entityClass, array $ids)
    {
        $ids = array_filter($ids);
        if (empty($ids)) {
            return [];
        }

        $key = array_search(OwnerHelper::CURRENT_USER, $ids);
        if ($key !== false) {
            unset($ids[$key]);
        }

        $result        = [];
        $identityField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
        if ($ids) {
            $result = $this->entityManager->getRepository($entityClass)->findBy([$identityField => $ids]);
        }

        if ($key !== false) {
            $result[] = [
                $identityField => OwnerHelper::CURRENT_USER
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
