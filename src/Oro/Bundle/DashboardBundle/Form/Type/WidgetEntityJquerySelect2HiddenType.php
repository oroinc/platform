<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * This widget must not has any model transformers that converts entities identifiers to object because it
 * stores model data in database widget options.
 * @see \Oro\Bundle\DashboardBundle\Controller\DashboardController::configureAction
 */
class WidgetEntityJquerySelect2HiddenType extends OroJquerySelect2HiddenType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                if (is_array($data)) {
                    $data = array_filter($data);
                    $event->setData($data);
                }
            }
        );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $vars = [
            'configs' => $options['configs'],
            'excluded' => (array)$options['excluded']
        ];

        $multiple = isset($options['configs']['multiple']) && $options['configs']['multiple'];

        if ($form->getData()) {
            $result = [];
            /** @var ConverterInterface $converter */
            $converter = $options['converter'];
            if ($multiple) {
                $ids = $form->getData();
            } else {
                $ids = [$form->getData()];
            }
            $items = $this->getEntitiesByIdentifiers($options['entity_class'], $ids);
            $identityField = $this->getSingleEntityIdentifierFieldName($options['entity_class']);
            $ids = [];
            foreach ($items as $item) {
                $item = $converter->convertItem($item);
                $result[] = $item;
                $ids[] = $item[$identityField];
            }

            if (!$multiple && $result) {
                $result = $result[0];
            }

            $vars['value'] = implode(',', $ids);
            $vars['attr'] = [
                'data-selected-data' => json_encode($result)
            ];
        }

        $view->vars = array_replace_recursive($view->vars, $vars);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_widget_entity_jqueryselect2_hidden';
    }

    protected function getEntitiesByIdentifiers(string $entityClass, array $ids): array
    {
        $ids = array_filter($ids);
        if (empty($ids)) {
            return [];
        }

        $identityField = $this->getSingleEntityIdentifierFieldName($entityClass);

        return $this->doctrine->getRepository($entityClass)->findBy([$identityField => $ids]);
    }

    protected function getSingleEntityIdentifierFieldName(string $entityClass): string
    {
        $metadata = $this->doctrine->getManagerForClass($entityClass)?->getClassMetadata($entityClass);
        $fieldNames = null !== $metadata ? $metadata->getIdentifierFieldNames() : [];
        if (\count($fieldNames) !== 1) {
            throw new InvalidEntityException(\sprintf(
                'Can\'t get single identifier field name for "%s" entity.',
                $entityClass
            ));
        }

        return reset($fieldNames);
    }
}
