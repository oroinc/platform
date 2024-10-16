<?php

namespace Oro\Bundle\ScopeBundle\Form\Type;

use Oro\Bundle\ScopeBundle\Form\DataTransformer\ScopeTransformer;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopeType extends AbstractType
{
    const NAME = 'oro_scope';
    const SCOPE_TYPE_OPTION = 'scope_type';
    const SCOPE_FIELDS_OPTION = 'scope_fields';

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var array
     */
    protected $scopeFields;

    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            self::SCOPE_TYPE_OPTION,
        ]);
        $resolver->setAllowedTypes(self::SCOPE_TYPE_OPTION, ['string']);
        $resolver->setDefaults([
            'error_bubbling' => false,
            self::SCOPE_FIELDS_OPTION => []
        ]);

        $resolver->setNormalizer(
            self::SCOPE_FIELDS_OPTION,
            function (Options $options) {
                return $this->scopeManager->getScopeEntities($options[self::SCOPE_TYPE_OPTION]);
            }
        );
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(
            new ScopeTransformer($this->scopeManager, $options[self::SCOPE_TYPE_OPTION])
        );
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var FormView[] $fields */
        $fields = [];
        foreach (array_reverse(array_keys($options[self::SCOPE_FIELDS_OPTION])) as $field) {
            if ($view->offsetExists($field)) {
                $fields[$field] = $view->offsetGet($field);
                $view->offsetUnset($field);
            }
        }

        $view->children = $fields;

        parent::finishView($view, $form, $options);
    }

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
