<?php

namespace Oro\Bundle\ScopeBundle\Form\Type;

use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\Form\AbstractType;
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

    /**
     * @param ScopeManager $scopeManager
     */
    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            self::SCOPE_TYPE_OPTION,
        ]);
        $resolver->setAllowedTypes(self::SCOPE_TYPE_OPTION, ['string']);
        $resolver->setDefaults([
            self::SCOPE_FIELDS_OPTION => []
        ]);

        $resolver->setNormalizer(
            self::SCOPE_FIELDS_OPTION,
            function (Options $options) {
                return $this->scopeManager->getScopeEntities($options[self::SCOPE_TYPE_OPTION]);
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
        return self::NAME;
    }
}
