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

    /**
     * @param LayoutManager $layoutManager
     */
    public function __construct(LayoutManager $layoutManager)
    {
        $this->layoutManager = $layoutManager;
    }

    /**
     * @param EmbeddedForm  $formEntity
     * @param FormInterface $form
     * @param string|null   $formLayout
     *
     * @return Layout
     */
    public function getFormLayout(EmbeddedForm $formEntity, FormInterface $form, $formLayout = null)
    {
        $layoutContext = new LayoutContext();
        $layoutBuilder = $this->getLayoutBuilder($formEntity);
        $layoutBuilder->add(
            'form',
            'content',
            new EmbedFormType(),
            [
                'form'        => $form->createView(),
                'form_layout' => $formLayout
            ]
        );

        $layout = $layoutBuilder->getLayout($layoutContext);
        $layout->setTheme('OroEmbeddedFormBundle:Layout:embed_form.html.twig');

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
        $layout->setTheme('OroEmbeddedFormBundle:Layout:embed_form.html.twig');

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
            ->add('content', 'root', 'body');

        return $layoutBuilder;
    }
}
