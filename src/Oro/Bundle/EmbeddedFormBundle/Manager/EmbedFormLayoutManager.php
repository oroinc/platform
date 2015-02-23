<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormSuccessType;
use Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type\EmbedFormType;

class EmbedFormLayoutManager
{
    /** @var LayoutManager */
    protected $layoutManager;

    /** @var EmbeddedFormManager */
    protected $formManager;

    /**
     * @param LayoutManager       $layoutManager
     * @param EmbeddedFormManager $formManager
     */
    public function __construct(LayoutManager $layoutManager, EmbeddedFormManager $formManager)
    {
        $this->layoutManager = $layoutManager;
        $this->formManager   = $formManager;
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
        $layoutBuilder = $this->layoutManager->getLayoutBuilder();

        $layoutBuilder
            ->add('root', null, 'root')
            ->add('head', 'root', 'head', ['title' => 'Form'])
            ->add('meta_charset', 'head', 'meta', ['charset' => 'utf-8'])
            ->add(
                'meta_x_ua_compatible',
                'head',
                'meta',
                ['http_equiv' => 'X-UA-Compatible', 'content' => 'IE=edge,chrome=1']
            )
            ->add(
                'meta_viewport',
                'head',
                'meta',
                ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1.0']
            )
            ->add('base_css', 'head', 'style')
            ->add('form_css', 'head', 'style', ['content' => $formEntity->getCss()])
            ->add('content', 'root', 'body')
            ->setBlockTheme('OroEmbeddedFormBundle:Layout:embed_form.html.twig');

        return $layoutBuilder;
    }
}
