<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

class EmbedFormLayoutManager
{
    /** @var LayoutManager */
    protected $layoutManager;

    /** @var EmbeddedFormManager */
    protected $formManager;

    /**
     * Use inline layout
     * @var bool
     */
    protected $inline = false;

    /**
     * @param LayoutManager       $layoutManager
     * @param EmbeddedFormManager $formManager
     */
    public function __construct(
        LayoutManager $layoutManager,
        EmbeddedFormManager $formManager
    ) {
        $this->layoutManager = $layoutManager;
        $this->formManager   = $formManager;
    }

    /**
     * @param EmbeddedForm  $formEntity
     * @param FormInterface $form
     *
     * @return Layout
     */
    public function getLayout(EmbeddedForm $formEntity, FormInterface $form = null)
    {
        $formTypeName = $formEntity->getFormType();
        $customLayout = $this->formManager->getCustomFormLayoutByFormType($formTypeName);

        $layoutContext = new LayoutContext();

        $layoutContext->getResolver()
            ->setRequired(['embedded_form_type'])
            ->setRequired(['embedded_form_inline'])
            ->setOptional(['embedded_form', 'embedded_form_custom_layout']);

        $layoutContext->set('theme', 'embedded_default');
        $layoutContext->set('embedded_form', null === $form ? null : new FormAccessor($form));
        $layoutContext->set('embedded_form_type', $formTypeName);
        $layoutContext->set('embedded_form_custom_layout', $customLayout);
        $layoutContext->set('embedded_form_inline', $this->inline);
        $layoutContext->data()->set('embedded_form_entity', '', $formEntity);

        $layoutBuilder = $this->layoutManager->getLayoutBuilder();
        // TODO discuss adding root automatically
        $layoutBuilder->add('root', null, 'root');

        return $layoutBuilder->getLayout($layoutContext);
    }

    /**
     * @param bool $inline
     */
    public function setInline($inline)
    {
        $this->inline = (bool)$inline;
    }
}
