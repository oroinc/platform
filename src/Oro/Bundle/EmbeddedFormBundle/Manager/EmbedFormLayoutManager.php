<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\LayoutBuilderInterface;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormSuccessType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormType;

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
    public function getFormLayout(EmbeddedForm $formEntity, FormInterface $form)
    {
        $layoutContext = new LayoutContext();

        // TODO discuss adding not registered blocks and passing not static options
        $layoutBuilder = $this->getLayoutBuilder($formEntity);
        $layoutBuilder->add(
            'form',
            'content',
            new EmbedFormType(),
            [
                'form'        => $form->createView(),
                // @deprecated since 1.7. Kept for backward compatibility
                'form_layout' => $this->formManager->getCustomFormLayoutByFormType($formEntity->getFormType())
            ]
        );

        $typeInstance = $this->formManager->getTypeInstance($formEntity->getFormType());
        if ($typeInstance instanceof LayoutUpdateInterface) {
            $typeInstance->updateLayout($layoutBuilder);
        }
        $layoutContext->getDataResolver()->setOptional(['embedded_form']);
        $layoutContext->set('embedded_form', $form);

        $layout = $layoutBuilder->getLayout($layoutContext);

        return $layout;
    }

    /**
     * @param EmbeddedForm $formEntity
     *
     * @return Layout
     */
    public function getFormSuccessLayout(EmbeddedForm $formEntity)
    {
        $layoutContext = new LayoutContext();
        $layoutBuilder = $this->getLayoutBuilder($formEntity);
        $layoutBuilder->add(
            'success_message',
            'content',
            new EmbedFormSuccessType(),
            [
                'message' => $formEntity->getSuccessMessage(),
                'form_id' => $formEntity->getId()
            ]
        );

        $layout = $layoutBuilder->getLayout($layoutContext);

        return $layout;
    }

    /**
     * @param EmbeddedForm $formEntity
     *
     * @return LayoutBuilderInterface
     */
    protected function getLayoutBuilder(EmbeddedForm $formEntity)
    {
        $this->themeManager->setActiveTheme('embedded_default');

        $layoutBuilder = $this->layoutManager->getLayoutBuilder();
        // TODO discuss adding root automatically
        $layoutBuilder->add('root', null, 'root');
        $layoutBuilder->setOption('form_css', 'content', $formEntity->getCss());

        return $layoutBuilder;
    }
}
