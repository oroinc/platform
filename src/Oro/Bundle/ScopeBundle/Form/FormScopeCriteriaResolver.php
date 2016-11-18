<?php

namespace Oro\Bundle\ScopeBundle\Form;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Symfony\Component\Form\FormInterface;

class FormScopeCriteriaResolver
{
    const SCOPE = 'scope';
    const CONTEXT = 'context';
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param ScopeManager $scopeManager
     */
    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    /**
     * @param FormInterface $form
     * @param string $type
     * @return ScopeCriteria
     */
    public function resolve(FormInterface $form, $type)
    {
        $context = $this->getFormScopeContext($form, $type);
        return $this->scopeManager->getCriteria($type, $context);
    }

    /**
     * @param FormInterface $form
     * @param string $type
     * @return array
     */
    protected function getFormScopeContext(FormInterface $form, $type)
    {
        $context = [];
        if ($form->getConfig()->hasOption(self::CONTEXT)) {
            $context = $form->getConfig()->getOption(self::CONTEXT);
        } elseif ($form->getConfig()->hasOption(self::SCOPE)) {
            $scope = $form->getConfig()->getOption(self::SCOPE);

            if ($scope instanceof Scope) {
                $context = $this->scopeManager->getCriteriaByScope($scope, $type)->toArray();
            }
        }

        $parentForm = $form->getParent();
        if (null !== $parentForm) {
            $context = array_replace($this->getFormScopeContext($parentForm, $type), $context);
        }

        return $context;
    }
}
