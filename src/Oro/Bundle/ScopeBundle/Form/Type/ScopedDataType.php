<?php

namespace Oro\Bundle\ScopeBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Form\FormScopeCriteriaResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScopedDataType extends AbstractType
{
    const NAME = 'oro_scoped_data_type';
    const SCOPES_OPTION = 'scopes';
    const TYPE_OPTION = 'type';
    const OPTIONS_OPTION = 'options';
    const PRELOADED_SCOPES_OPTION = 'preloaded_scopes';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

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

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                self::TYPE_OPTION,
                self::SCOPES_OPTION,
            ]
        );
        $resolver->setAllowedTypes(self::SCOPES_OPTION, 'array');

        $resolver->setDefaults(
            [
                self::PRELOADED_SCOPES_OPTION => [],
                self::OPTIONS_OPTION => null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options[self::PRELOADED_SCOPES_OPTION])) {
            $preloadedScopes = $options[self::PRELOADED_SCOPES_OPTION];
        } else {
            $preloadedScopes = $options[self::SCOPES_OPTION];
        }

        $options[self::OPTIONS_OPTION]['data'] = $options['data'];
        $options[self::OPTIONS_OPTION]['ownership_disabled'] = true;

        /** @var Scope $scope */
        foreach ($preloadedScopes as $scope) {
            if (!$this->isAllowedScope($options[self::SCOPES_OPTION], $scope->getId())) {
                throw new \InvalidArgumentException(
                    sprintf('Scope id %s is not in allowed form scopes', $scope->getId())
                );
            }
            $options['options'][FormScopeCriteriaResolver::SCOPE] = $scope;
            $builder->add(
                $scope->getId(),
                $options[self::TYPE_OPTION],
                $options[self::OPTIONS_OPTION]
            );
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars[self::SCOPES_OPTION] = $form->getConfig()->getOption(self::SCOPES_OPTION);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        foreach ($event->getData() as $scopeId => $value) {
            if ($form->has($scopeId)) {
                continue;
            }
            $this->addTypeByScope($form, $scopeId, $form->getData());
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        if (!is_array($event->getData())) {
            return;
        }
        foreach ($event->getData() as $scopeId => $value) {
            $this->addTypeByScope($event->getForm(), $scopeId, $value);
        }
    }

    /**
     * @param FormInterface $form
     * @param int $scopeId
     * @param array|object $data
     */
    protected function addTypeByScope(FormInterface $form, $scopeId, $data)
    {
        if (!$this->isAllowedScope($form->getConfig()->getOption(self::SCOPES_OPTION), $scopeId)) {
            throw new \InvalidArgumentException(sprintf('Scope id %s is not in allowed form scopes', $scopeId));
        }
        $options = $form->getConfig()->getOption(self::OPTIONS_OPTION);
        $options['data'] = $data;
        $options['ownership_disabled'] = true;

        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(Scope::class);
        $options[FormScopeCriteriaResolver::SCOPE] = $em->getReference(Scope::class, $scopeId);

        $form->add($scopeId, $form->getConfig()->getOption(self::TYPE_OPTION), $options);
    }

    /**
     * @param Scope[] $scopes
     * @param int $scopeId
     * @return bool
     */
    protected function isAllowedScope(array $scopes, $scopeId)
    {
        foreach ($scopes as $scope) {
            if ($scopeId === $scope->getId()) {
                return true;
            }
        }

        return false;
    }
}
