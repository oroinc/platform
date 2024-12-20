<?php

namespace Oro\Bundle\OrganizationBundle\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetEntityJquerySelect2HiddenType;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provide business unit select
 * create select field
 */
class WidgetBusinessUnitSelectType extends WidgetEntityJquerySelect2HiddenType
{
    const NAME = 'oro_type_widget_business_unit_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'widget_owner_business_units',
                'configs'            => [
                    'multiple'    => true,
                    'placeholder' => 'oro.dashboard.form.choose_business_unit',
                    'allowClear'              => true,
                    'result_template_twig'    => '@OroOrganization/BusinessUnit/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@OroOrganization/BusinessUnit/Autocomplete/selection.html.twig',
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
    #[\Override]
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

    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
