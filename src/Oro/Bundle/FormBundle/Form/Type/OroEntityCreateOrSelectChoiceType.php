<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreateOrSelectTransformer;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type to Choose existing entity or Create new one within single page.
 */
class OroEntityCreateOrSelectChoiceType extends AbstractType
{
    const NAME = 'oro_entity_create_or_select_choice';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    protected $validationEnabledModes = [
        OroEntityCreateOrSelectType::MODE_CREATE,
        OroEntityCreateOrSelectType::MODE_EDIT,
    ];

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($options) {
                $data = $event->getData();
                $mode = !empty($data['mode']) ? $data['mode'] : $options['mode'];

                $isEntityPreviewForm = !empty($data['existing_entity']) && $options['disabled_edit_form'];
                if ($isEntityPreviewForm || !in_array($mode, $this->validationEnabledModes, true)) {
                    $this->disableNewEntityValidation($event->getForm());
                }
            }
        );

        $builder->addViewTransformer(
            new EntityCreateOrSelectTransformer(
                $this->doctrineHelper,
                $options['class'],
                $options['mode'],
                $options['editable']
            )
        );

        $builder->add(
            'existing_entity',
            $options['select_entity_form_type'],
            array_merge(
                ['required' => $options['required']],
                $options['select_entity_form_options']
            )
        );

        $builder->add(
            'new_entity',
            $options['create_entity_form_type'],
            $this->getNewEntityFormOptions($options)
        );

        $builder->add('mode', HiddenType::class, [
            'data' => $options['mode'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'class',
            'create_entity_form_type',
            'select_entity_form_type',
        ]);

        $resolver->setDefaults([
            'data_class' => null,
            'create_entity_form_options' => [],
            'select_entity_form_options' => [],
            'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
            'edit_route' => null,
            'editable' => false,
            /**
             * flag was added to add the ability to preview existing entity data within edit forms without a need
             * to leave a page. In this case when cascade operations are not configured edits will be not persisted
             * which will confuse user. So we show form in disabled state.
             */
            'disabled_edit_form' => false
        ]);

        $resolver->setNormalizer(
            'editable',
            static function (Options $options, $value) {
                if (!$options['edit_route']) {
                    return false;
                }

                return $value;
            }
        );
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
        return static::NAME;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getNewEntityFormOptions(array $options)
    {
        return array_merge(
            $options['create_entity_form_options'],
            [
                'required' => $options['required'],
                'data_class' => $options['class'],
            ]
        );
    }

    protected function disableNewEntityValidation(FormInterface $form)
    {
        FormUtils::replaceField($form, 'new_entity', ['validation_groups' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['editable'] = $options['editable'];
        $view->vars['disabled_edit_form'] = $options['disabled_edit_form'];
        $view->vars['edit_route'] = $options['edit_route'];
    }
}
