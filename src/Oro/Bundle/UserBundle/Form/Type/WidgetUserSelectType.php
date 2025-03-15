<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetEntityJquerySelect2HiddenType;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provide select user list
 * create select field
 */
class WidgetUserSelectType extends WidgetEntityJquerySelect2HiddenType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'widget_owner_users',
                'configs' => [
                    'multiple' => true,
                    'placeholder' => 'oro.user.form.choose_user',
                    'allowClear' => true,
                    'result_template_twig' => '@OroUser/User/Autocomplete/Widget/result.html.twig',
                    'selection_template_twig' => '@OroUser/User/Autocomplete/Widget/selection.html.twig',
                ]
            ]
        );
    }

    #[\Override]
    protected function getEntitiesByIdentifiers(string $entityClass, array $ids): array
    {
        $ids = array_filter($ids);
        if (empty($ids)) {
            return [];
        }

        $key = array_search(OwnerHelper::CURRENT_USER, $ids);
        if ($key !== false) {
            unset($ids[$key]);
        }

        $result = [];
        $identityField = $this->getSingleEntityIdentifierFieldName($entityClass);
        if ($ids) {
            $result = $this->doctrine->getRepository($entityClass)->findBy([$identityField => $ids]);
        }

        if ($key !== false) {
            $result[] = [
                $identityField => OwnerHelper::CURRENT_USER
            ];
        }

        return $result;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_type_widget_user_select';
    }
}
