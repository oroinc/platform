<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ObjectLabelType extends AbstractType
{
    /** @var EntityClassNameHelper */
    protected $classNameHelper;

    /**
     * @param EntityClassNameHelper $classNameHelper
     */
    public function __construct(EntityClassNameHelper $classNameHelper)
    {
        $this->classNameHelper = $classNameHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_acl_label';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $identity = $view->parent->vars['value']->getId();
        $className = str_replace('entity:', '', $identity);

        // add url params for field level aces
        if (strpos($identity, 'entity:') === 0 && $className != ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            $role = $view->parent->parent->parent->parent->vars['value'];
            $view->vars['roleId'] = $role ? $role->getId() : null;
            $view->vars['urlSafeClassName'] = $this->classNameHelper->getUrlSafeClassName($className);
            $view->vars['className'] = $className;
            $view->vars['isPlatformRole'] = ($role instanceof Role);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }
}
