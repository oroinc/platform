<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

use Oro\Bundle\LayoutBundle\Tests\Fixtures\UserNameType;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class RendererTest extends LayoutTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testHtmlRenderingForCoreBlocksByTwigRenderer()
    {
        if (!$this->getContainer()->hasParameter('oro_layout.twig.resources')) {
            $this->markTestSkipped('TWIG renderer is not enabled.');
        }

        $context = new LayoutContext();
        $context->getResolver()->setDefined(['form', 'body_class']);
        $form = $this->getTestForm();
        $context->data()->set('form', $form->createView());
        $context->set('body_class', 'test-body');

        // revert TWIG form renderer to Symfony's default theme
        $this->getContainer()->get('twig.form.renderer.alias')->setTheme(
            $context->data()->get('form'),
            'form_div_layout.html.twig'
        );

        $result   = $this->getCoreBlocksTestLayout($context)->setRenderer('twig')->render();
        $expected = $this->getCoreBlocksTestLayoutResult(
            $this->getTwigFormLayoutResult()
        );

        $this->assertHtmlEquals($expected, $result);
    }

    public function testHtmlRenderingForCoreBlocksByPhpRenderer()
    {
        if (!$this->getContainer()->hasParameter('oro_layout.php.resources')) {
            $this->markTestSkipped('PHP renderer is not enabled.');
        }

        $context = new LayoutContext();
        $context->getResolver()->setDefined(['form', 'body_class']);
        $form = $this->getTestForm();
        $context->data()->set('form', $form->createView());
        $context->set('body_class', 'test-body');

        $result   = $this->getCoreBlocksTestLayout($context)->setRenderer('php')->render();

        $expected = $this->getCoreBlocksTestLayoutResult(
            $this->getPhpFormLayoutResult()
        );
        $this->assertHtmlEquals($expected, $result);
    }

    public function testHtmlRenderingForFormStartByTwigRenderer()
    {
        if (!$this->getContainer()->hasParameter('oro_layout.twig.resources')) {
            $this->markTestSkipped('TWIG renderer is not enabled.');
        }

        $context = new LayoutContext();
        $context->getResolver()->setDefined(['form']);
        $form = $this->getTestForm('test.php', 'patch');
        $context->data()->set('form', $form->createView());

        // revert TWIG form renderer to Symfony's default theme
        $this->getContainer()->get('twig.form.renderer.alias')->setTheme(
            $context->data()->get('form'),
            'form_div_layout.html.twig'
        );

        $layoutManager = $this->getContainer()->get('oro_layout.layout_manager');
        $result        = $layoutManager->getLayoutBuilder()
            ->add('form:start', null, 'form_start', ['form' => '=data["form"]'])
            ->getLayout($context)
            ->setRenderer('twig')
            ->render();

        $expected = $this->getFormStartTestLayoutResult();

        $this->assertHtmlEquals($expected, $result);
    }

    public function testHtmlRenderingForFormStartByPhpRenderer()
    {
        if (!$this->getContainer()->hasParameter('oro_layout.twig.resources')) {
            $this->markTestSkipped('TWIG renderer is not enabled.');
        }

        $context = new LayoutContext();
        $context->getResolver()->setDefined(['form']);
        $form = $this->getTestForm('test.php', 'patch');
        $context->data()->set('form', $form->createView());

        // revert TWIG form renderer to Symfony's default theme
        $this->getContainer()->get('twig.form.renderer.alias')->setTheme(
            $context->data()->get('form'),
            'form_div_layout.html.twig'
        );

        $layoutManager = $this->getContainer()->get('oro_layout.layout_manager');
        $result        = $layoutManager->getLayoutBuilder()
            ->add('form:start', null, 'form_start', ['form' => '=data["form"]'])
            ->getLayout($context)
            ->setRenderer('php')
            ->render();

        $expected = $this->getFormStartTestLayoutResult();

        $this->assertHtmlEquals($expected, $result);
    }

    /**
     * @param ContextInterface $context
     *
     * @return Layout
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getCoreBlocksTestLayout(ContextInterface $context)
    {
        /** @var LayoutManager $layoutManager */
        $layoutManager = $this->getContainer()->get('oro_layout.layout_manager');

        $layout = $layoutManager->getLayoutBuilder()
            ->add('root', null, 'root')
            ->add('head', 'root', 'head')
            ->add(
                'title',
                'head',
                'title',
                ['value' => 'Page Title']
            )
            ->add('meta', 'head', 'meta', ['charset' => 'UTF-8'])
            ->add('style', 'head', 'style', ['content' => 'body { color: red; }', 'scoped' => true])
            ->add(
                'external_style',
                'head',
                'style',
                [
                    'src' => '=data["asset"].getUrl("test.css")',
                    'scoped' => '=false'
                ]
            )
            ->add(
                'script',
                'head',
                'script',
                [
                    'content' => 'alert(\'test\');',
                    'async'   => true,
                    'defer'   => '=false'
                ]
            )
            ->add(
                'external_resource',
                'head',
                'external_resource',
                ['href' => 'test_external.css', 'rel' => 'stylesheet']
            )
            ->add(
                'content',
                'root',
                'body',
                [
                    'class_prefix' => 'content',
                    'attr' => [
                        'class' => '{{ class_prefix }}-body',
                        'data-json' => '{"0":"test1"}',
                    ],
                ]
            )
            ->add('list', 'content', 'list')
            ->add(
                'list_item_1',
                'list',
                'text',
                [
                    'text' => [
                        'label'      => 'Hi %val%!',
                        'parameters' => ['%val%' => 'World']
                    ]
                ]
            )
            ->add(
                'list_item_2_container',
                'list',
                'list_item',
                ['attr' => ['class' => 'list-item-2']]
            )
            ->add(
                'list_item_2',
                'list_item_2_container',
                'link',
                [
                    'path' => 'http://example.com',
                    'text' => [
                        'label'      => 'Hi %val%!',
                        'parameters' => ['%val%' => 'World']
                    ]
                ]
            )
            ->add('ordered_list', 'content', 'ordered_list', ['type' => 'a'])
            ->add(
                'ordered_list_item_1',
                'ordered_list',
                'text',
                [
                    'text' => [
                        'label'      => 'Hi %val%!',
                        'parameters' => ['%val%' => 'World']
                    ]
                ]
            )
            ->add(
                'ordered_list_item_2_container',
                'ordered_list',
                'list_item',
                ['attr' => ['class' => 'list-item-2']]
            )
            ->add(
                'ordered_list_item_2',
                'ordered_list_item_2_container',
                'link',
                [
                    'path' => 'http://example.com',
                    'text' => [
                        'label'      => 'Hi %val%!',
                        'parameters' => ['%val%' => 'World']
                    ]
                ]
            )
            ->add(
                'form',
                'content',
                'form_fields',
                ['form'=> '=data["form"]']
            )
            // test 'visible' option
            ->add('invisible_container', 'root', 'head', ['visible' => false])
            ->add('invisible_child', 'invisible_container', 'meta', ['charset' => 'invisible'])
            // test 'visible' option when its value is an expression
            ->add(
                'invisible_by_expr_raw_container',
                'root',
                'head',
                ['visible' => '=false']
            )
            ->add(
                'invisible_by_expr_raw_child',
                'invisible_by_expr_raw_container',
                'meta',
                ['charset' => 'invisible_by_expr_raw']
            )
            // test 'visible' option when its value is already assembled expression
            ->add('invisible_by_expr_container', 'root', 'head', ['visible' => '=false'])
            ->add('invisible_by_expr_child', 'invisible_by_expr_container', 'meta', ['charset' => 'invisible_by_expr'])
            // test buttons
            ->add(
                'button',
                'content',
                'button',
                ['name' => 'btn1', 'text' => 'Btn1', 'icon' => 'plus', 'vars' => ['icon_class' => 'hide-text']],
                null,
                true
            )
            ->add(
                'input_button',
                'content',
                'button',
                ['type' => 'input', 'action' => 'submit', 'name' => 'btn2', 'text' => 'Btn2'],
                'button'
            )
            ->add(
                'input_text',
                'content',
                'input',
                ['name' => 'search'],
                'button'
            )
            // test manipulations of 'class' attribute
            ->appendOption('content', 'attr.class', '="class1"~" "~"class2"')
            ->replaceOption('content', 'attr.class', 'class1', '=context["body_class"]')
            ->getLayout($context);

        return $layout;
    }

    /**
     * @param string|null $action
     * @param string|null $method
     *
     * @return FormInterface
     */
    protected function getTestForm($action = null, $method = null)
    {
        $options = ['csrf_protection' => false];
        if ($action) {
            $options['action'] = $action;
        }
        if ($method) {
            $options['method'] = $method;
        }
        /** @var FormFactoryInterface $formFactory */
        $formFactory = $this->getContainer()->get('form.factory');

        $form = $formFactory->createNamedBuilder(
            'form_for_layout_renderer_test',
            FormType::class,
            null,
            $options
        )
            ->add('user', UserNameType::class)
            ->add('jobTitle', TextType::class, ['label' => 'Job Title', 'required' => false])
            ->add(
                'gender',
                ChoiceType::class,
                [
                    'label'    => 'Gender',
                    'required' => false,
                    'choices'  => ['Male' => 'male', 'Female' => 'female'],
                    'expanded' => true
                ]
            )
            ->getForm();

        return $form;
    }

    /**
     * @param string $formLayout
     *
     * @return string
     */
    protected function getCoreBlocksTestLayoutResult($formLayout)
    {
        $expected = <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title>Page Title</title>
        <meta charset="UTF-8"/>
        <style type="text/css" scoped="scoped">
            body { color: red; }
        </style>
        <link rel="stylesheet" type="text/css" href="/test.css"/>
        <script type="text/javascript" async="async">
            alert('test');
        </script>
        <link rel="stylesheet" href="test_external.css"/>
    </head>
<body class="content-body test-body class2" data-json="{&quot;0&quot;:&quot;test1&quot;}">
    <button type="button" name="btn1"><i class="fa-plus hide-text"></i>Btn1</button>
    <input type="text" name="search"/>
    <input type="submit" name="btn2" value="Btn2"/>
    <ul>
        <li>Hi World!</li>
        <li class="list-item-2"><a href="http://example.com">Hi World!</a></li>
    </ul>
    <ol type="a">
        <li>Hi World!</li>
        <li class="list-item-2"><a href="http://example.com">Hi World!</a></li>
    </ol>
    {form_layout}
</body>
</html>
HTML;
        $expected = str_replace('{form_layout}', $formLayout, $expected);

        return $expected;
    }

    /**
     * @return string
     */
    protected function getTwigFormLayoutResult()
    {
        // @codingStandardsIgnoreStart
        $expected = <<<HTML
<div id="form_for_layout_renderer_test" data-ftid="form_for_layout_renderer_test" data-name="form__form-for-layout-renderer-test">
    <div>
        <label class="required">User</label>
        <div id="form_for_layout_renderer_test_user" data-ftid="form_for_layout_renderer_test_user" data-name="field__user">
            <div>
                <label for="form_for_layout_renderer_test_user_firstName" class="required">First Name</label>
                <input type="text"
                    id="form_for_layout_renderer_test_user_firstName"
                    name="form_for_layout_renderer_test[user][firstName]"
                    required="required"
                    data-ftid="form_for_layout_renderer_test_user_firstName" data-name="field__first-name"/>
            </div>
            <div>
                <label for="form_for_layout_renderer_test_user_lastName" class="required">Last Name</label>
                <input type="text"
                    id="form_for_layout_renderer_test_user_lastName"
                    name="form_for_layout_renderer_test[user][lastName]"
                    required="required"
                    data-ftid="form_for_layout_renderer_test_user_lastName" data-name="field__last-name"/>
            </div>
        </div>
    </div>
    <div>
        <label for="form_for_layout_renderer_test_jobTitle">Job Title</label>
        <input type="text"
            id="form_for_layout_renderer_test_jobTitle"
            name="form_for_layout_renderer_test[jobTitle]"
            data-ftid="form_for_layout_renderer_test_jobTitle" data-name="field__job-title"/>
    </div>
    <div>
        <label>Gender</label>
        <div id="form_for_layout_renderer_test_gender" data-ftid="form_for_layout_renderer_test_gender" data-name="field__gender">
            <input type="radio"
                id="form_for_layout_renderer_test_gender_placeholder"
                name="form_for_layout_renderer_test[gender]"
                data-ftid="form_for_layout_renderer_test_gender_placeholder" data-name="field__placeholder"
                value="" checked="checked"/>
            <label for="form_for_layout_renderer_test_gender_placeholder">None</label>
            <input type="radio"
                id="form_for_layout_renderer_test_gender_0"
                name="form_for_layout_renderer_test[gender]"
                data-ftid="form_for_layout_renderer_test_gender_0" data-name="field__0"
                value="male"/>
            <label for="form_for_layout_renderer_test_gender_0">Male</label>
            <input type="radio"
                id="form_for_layout_renderer_test_gender_1"
                name="form_for_layout_renderer_test[gender]"
                data-ftid="form_for_layout_renderer_test_gender_1" data-name="field__1"
                value="female"/>
            <label for="form_for_layout_renderer_test_gender_1">Female</label>
        </div>
    </div>
</div>
HTML;
        // @codingStandardsIgnoreEnd

        return $expected;
    }

    /**
     * @return string
     */
    protected function getPhpFormLayoutResult()
    {
        // @codingStandardsIgnoreStart
        $expected = <<<HTML
<div id="form_for_layout_renderer_test" data-ftid="form_for_layout_renderer_test" data-name="form__form-for-layout-renderer-test">
    <div>
        <label data-ftid="form_for_layout_renderer_test_user" data-name="field__user" class="required">User</label>
        <div id="form_for_layout_renderer_test_user" data-ftid="form_for_layout_renderer_test_user" data-name="field__user">
            <div>
                <label data-ftid="form_for_layout_renderer_test_user_firstName" data-name="field__first-name" class="required" for="form_for_layout_renderer_test_user_firstName">First Name</label>
                <input type="text"
                    id="form_for_layout_renderer_test_user_firstName"
                    name="form_for_layout_renderer_test[user][firstName]"
                    required="required"
                    data-ftid="form_for_layout_renderer_test_user_firstName" data-name="field__first-name"/>
            </div>
            <div>
                <label data-ftid="form_for_layout_renderer_test_user_lastName" data-name="field__last-name" class="required" for="form_for_layout_renderer_test_user_lastName">Last Name</label>
                <input type="text"
                    id="form_for_layout_renderer_test_user_lastName"
                    name="form_for_layout_renderer_test[user][lastName]"
                    required="required"
                    data-ftid="form_for_layout_renderer_test_user_lastName" data-name="field__last-name"/>
            </div>
        </div>
    </div>
    <div>
        <label data-ftid="form_for_layout_renderer_test_jobTitle" data-name="field__job-title" for="form_for_layout_renderer_test_jobTitle">Job Title</label>
        <input type="text"
            id="form_for_layout_renderer_test_jobTitle"
            name="form_for_layout_renderer_test[jobTitle]"
            data-ftid="form_for_layout_renderer_test_jobTitle" data-name="field__job-title"/>
    </div>
    <div>
        <label>Gender</label>
        <div id="form_for_layout_renderer_test_gender" data-ftid="form_for_layout_renderer_test_gender" data-name="field__gender">
            <input type="radio"
                id="form_for_layout_renderer_test_gender_placeholder"
                name="form_for_layout_renderer_test[gender]"
                data-ftid="form_for_layout_renderer_test_gender_placeholder" data-name="field__placeholder"
                value=""  checked="checked"/>
            <label data-ftid="form_for_layout_renderer_test_gender_placeholder" data-name="field__placeholder" for="form_for_layout_renderer_test_gender_placeholder">None</label>
            <input type="radio"
                id="form_for_layout_renderer_test_gender_0"
                name="form_for_layout_renderer_test[gender]"
                data-ftid="form_for_layout_renderer_test_gender_0" data-name="field__0"
                value="male"/>
            <label data-ftid="form_for_layout_renderer_test_gender_0" data-name="field__0" for="form_for_layout_renderer_test_gender_0">Male</label>
            <input type="radio"
                id="form_for_layout_renderer_test_gender_1"
                name="form_for_layout_renderer_test[gender]"
                data-ftid="form_for_layout_renderer_test_gender_1" data-name="field__1"
                value="female"/>
            <label data-ftid="form_for_layout_renderer_test_gender_1" data-name="field__1" for="form_for_layout_renderer_test_gender_1">Female</label>
        </div>
    </div>
</div>
HTML;
        // @codingStandardsIgnoreEnd

        return $expected;
    }

    /**
     * @return string
     */
    protected function getFormStartTestLayoutResult()
    {
        // @codingStandardsIgnoreStart
        $expected = <<<HTML
<form name="form_for_layout_renderer_test" method="post" action="test.php" data-ftid="form_for_layout_renderer_test" data-name="form__form-for-layout-renderer-test" id="form_for_layout_renderer_test">
<input type="hidden" name="_method" value="PATCH"/>
HTML;
        // @codingStandardsIgnoreEnd

        return $expected;
    }
}
