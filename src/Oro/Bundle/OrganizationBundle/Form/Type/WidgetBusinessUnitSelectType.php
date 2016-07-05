<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;
use Oro\Bundle\DashboardBundle\Form\Type\WidgetEntityJquerySelect2HiddenType;

class WidgetBusinessUnitSelectType extends WidgetEntityJquerySelect2HiddenType
{
    const NAME = 'oro_type_widget_business_unit_select';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'widget_owner_business_units',
                'configs'            => [
                    'multiple'    => true,
                    'width'       => '400px',
                    'placeholder' => 'oro.dashboard.form.choose_business_unit',
                    'allowClear'              => true,
                    'result_template_twig'    => 'OroOrganizationBundle:BusinessUnit:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroOrganizationBundle:BusinessUnit:Autocomplete/selection.html.twig',
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
        $key = array_search(OwnerHelper::CURRENT_BUSINESS_UNIT, $ids);
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
                $identityField => OwnerHelper::CURRENT_BUSINESS_UNIT
            ];
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
