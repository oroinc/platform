<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

class EmbedFormLayoutManager
{
    /** @var LayoutManager */
    protected $layoutManager;

    /** @var EmbeddedFormManager */
    protected $formManager;

    /** @var ThemeManager */
    protected $themeManager;

    /**
     * @param LayoutManager       $layoutManager
     * @param EmbeddedFormManager $formManager
     * @param ThemeManager        $themeManager
     */
    public function __construct(
        LayoutManager $layoutManager,
        EmbeddedFormManager $formManager,
        ThemeManager $themeManager
    ) {
        $this->layoutManager = $layoutManager;
        $this->formManager   = $formManager;
        $this->themeManager  = $themeManager;
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
            ->setOptional(['embedded_form', 'embedded_form_custom_layout']);

        $layoutContext->set('theme', 'embedded_default');
        $layoutContext->set('embedded_form', null === $form ? null : new FormAccessor($form));
        $layoutContext->set('embedded_form_type', $formTypeName);
        $layoutContext->set('embedded_form_custom_layout', $customLayout);
        $layoutContext->getData()->set('embedded_form_entity', '', $formEntity);

        $layoutBuilder = $this->layoutManager->getLayoutBuilder();
        // TODO discuss adding root automatically
        $layoutBuilder->add('root', null, 'root');

        return $layoutBuilder->getLayout($layoutContext);
    }
}
