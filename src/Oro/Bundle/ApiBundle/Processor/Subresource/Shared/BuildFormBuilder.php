<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Builds the form builder based on the parent entity configuration
 * and sets it to the Context.
 */
class BuildFormBuilder implements ProcessorInterface
{
    /** @var FormHelper */
    protected $formHelper;

    /**
     * @param FormHelper $formHelper
     */
    public function __construct(FormHelper $formHelper)
    {
        $this->formHelper = $formHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ChangeRelationshipContext $context */

        if ($context->hasFormBuilder()) {
            // the form builder is already built
            return;
        }
        if ($context->hasForm()) {
            // the form is already built
            return;
        }

        $context->setFormBuilder($this->getFormBuilder($context));
    }

    /**
     * @param ChangeRelationshipContext $context
     *
     * @return FormBuilderInterface
     */
    protected function getFormBuilder(ChangeRelationshipContext $context)
    {
        $parentConfig = $context->getParentConfig();
        $associationName = $context->getAssociationName();

        $formEventSubscribers = null;
        if (!$parentConfig->getFormType()) {
            $formEventSubscribers = $parentConfig->getFormEventSubscribers();
        }
        $formBuilder = $this->formHelper->createFormBuilder(
            'form',
            $context->getParentEntity(),
            ['data_class' => $this->getFormDataClass($context, $parentConfig)],
            $formEventSubscribers
        );
        $this->formHelper->addFormField(
            $formBuilder,
            $associationName,
            $parentConfig->getField($associationName),
            $context->getParentMetadata()->getAssociation($associationName)
        );

        return $formBuilder;
    }

    /**
     * @param ChangeRelationshipContext $context
     * @param EntityDefinitionConfig    $parentConfig
     *
     * @return string
     */
    protected function getFormDataClass(ChangeRelationshipContext $context, EntityDefinitionConfig $parentConfig)
    {
        $parentFormOptions = $parentConfig->getFormOptions();
        if (null !== $parentFormOptions && array_key_exists('data_class', $parentFormOptions)) {
            return $parentFormOptions['data_class'];
        }

        $dataClass = $context->getParentClassName();
        $parentEntity = $context->getParentEntity();
        if (is_object($parentEntity)) {
            $parentResourceClass = $parentConfig->getParentResourceClass();
            if ($parentResourceClass) {
                $entityClass = ClassUtils::getClass($parentEntity);
                if ($entityClass !== $dataClass && $entityClass === $parentResourceClass) {
                    $dataClass = $parentResourceClass;
                }
            }
        }

        return $dataClass;
    }
}
