<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;
use Oro\Bundle\LayoutBundle\Tests\Fixtures\UserNameType;

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
        $context->getResolver()->setOptional(['form', 'body_class']);
        $form = $this->getTestForm();
        $context->set('form', new FormAccessor($form));
        $context->set('body_class', 'test-body');

        // revert TWIG form renderer to Symfony's default theme
        $this->getContainer()->get('twig.form.renderer')->setTheme(
            $context->get('form')->getView(),
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
        $context->getResolver()->setOptional(['form', 'body_class']);
        $form = $this->getTestForm();
        $context->set('form', new FormAccessor($form));
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
        $context->getResolver()->setOptional(['form']);
        $form = $this->getTestForm();
        $context->set('form', new FormAccessor($form, FormAction::createByPath('test.php'), 'patch'));

        // revert TWIG form renderer to Symfony's default theme
        $this->getContainer()->get('twig.form.renderer')->setTheme(
            $context->get('form')->getView(),
            'form_div_layout.html.twig'
        );

        $layoutManager = $this->getContainer()->get('oro_layout.layout_manager');
        $result        = $layoutManager->getLayoutBuilder()
            ->add('form:start', null, 'form_start')
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
        $context->getResolver()->setOptional(['form']);
        $form = $this->getTestForm();
        $context->set('form', new FormAccessor($form, FormAction::createByPath('test.php'), 'patch'));

        // revert TWIG form renderer to Symfony's default theme
        $this->getContainer()->get('twig.form.renderer')->setTheme(
            $context->get('form')->getView(),
            'form_div_layout.html.twig'
        );

        $layoutManager = $this->getContainer()->get('oro_layout.layout_manager');
        $result        = $layoutManager->getLayoutBuilder()
            ->add('form:start', null, 'form_start')
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
                ['value' => ['First', 'Second'], 'separator' => ' - ', 'reverse' => true]
            )
            ->add('meta', 'head', 'meta', ['charset' => 'UTF-8'])
            ->add('style', 'head', 'style', ['content' => 'body { color: red; }', 'scoped' => true])
            ->add(
                'external_style',
                'head',
                'style',
                [
                    'src' => ['@asset' => 'test.css'],
                    'scoped' => new Condition\FalseCondition()
                ]
            )
            ->add(
                'script',
                'head',
                'script',
                [
                    'content' => 'alert(\'test\');',
                    'async'   => true,
                    'defer'   => new Condition\FalseCondition()
                ]
            )
            ->add('external_resource', 'head', 'external_resource', ['href' => 'test.css', 'rel' => 'stylesheet'])
            ->add(
                'content',
                'root',
                'body',
                [
                    'class_prefix' => 'content',
                    'attr' => [
                        'class' => '{{ class_prefix }}-body',
                        'data-json' => ['test1'],
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
                [
                    'preferred_fields' => ['jobTitle', 'user.lastName'],
                    'groups'           => [
                        'general'    => [
                            'title'  => 'General Info',
                            'fields' => ['user.firstName', 'user.lastName']
                        ],
                        'additional' => [
                            'title'   => 'Additional Info',
                            'default' => true
                        ]
                    ]
                ]
            )
            // swap 'general' and 'additional' groups to check that a layout update
            // can be applied for items added by a block type
            ->move('form_fields:group_general', null, 'form_fields:group_additional')
            // test 'visible' option
            ->add('invisible_container', 'root', 'head', ['visible' => false])
            ->add('invisible_child', 'invisible_container', 'meta', ['charset' => 'invisible'])
            // test 'visible' option when its value is an expression
            ->add(
                'invisible_by_expr_raw_container',
                'root',
                'head',
                ['visible' => ['@false' => null]]
            )
            ->add(
                'invisible_by_expr_raw_child',
                'invisible_by_expr_raw_container',
                'meta',
                ['charset' => 'invisible_by_expr_raw']
            )
            // test 'visible' option when its value is already assembled expression
            ->add('invisible_by_expr_container', 'root', 'head', ['visible' => new Condition\FalseCondition()])
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
            ->appendOption('content', 'attr.class', ['@join' => [' ', 'class1', 'class2']])
            ->replaceOption('content', 'attr.class', 'class1', ['@value' => ['$context.body_class']])
            ->getLayout($context);

        return $layout;
    }

    /**
     * @return FormInterface
     */
    protected function getTestForm()
    {
        /** @var FormFactoryInterface $formFactory */
        $formFactory = $this->getContainer()->get('form.factory');

        $form = $formFactory->createNamedBuilder(
            'form_for_layout_renderer_test',
            'form',
            null,
            ['csrf_protection' => false]
        )
            ->add('user', new UserNameType())
            ->add('jobTitle', 'text', ['label' => 'Job Title', 'required' => false])
            ->add(
                'gender',
                'choice',
                [
                    'label'    => 'Gender',
                    'required' => false,
                    'choices'  => ['male' => 'Male', 'female' => 'Female'],
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
        <title>Second - First</title>
        <meta charset="UTF-8"/>
        <style type="text/css" scoped="scoped">
            body { color: red; }
        </style>
        <link rel="stylesheet" type="text/css" href="/test.css"/>
        <script type="text/javascript" async="async">
            alert('test');
        </script>
        <link rel="stylesheet" href="test.css"/>
    </head>
<body class="content-body test-body class2" data-json="{&quot;0&quot;:&quot;test1&quot;}">
    <button name="btn1"><i class="icon-plus hide-text"></i>Btn1</button>
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
                value=""/>
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
        <label class="required">User</label>
        <div id="form_for_layout_renderer_test_user" data-ftid="form_for_layout_renderer_test_user" data-name="field__user">
            <div>
                <label class="required" for="form_for_layout_renderer_test_user_firstName">First Name</label>
                <input type="text"
                    id="form_for_layout_renderer_test_user_firstName"
                    name="form_for_layout_renderer_test[user][firstName]"
                    required="required"
                    data-ftid="form_for_layout_renderer_test_user_firstName" data-name="field__first-name"/>
            </div>
            <div>
                <label class="required" for="form_for_layout_renderer_test_user_lastName">Last Name</label>
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
                value=""/>
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
    protected function getFormStartTestLayoutResult()
    {
        // @codingStandardsIgnoreStart
        $expected = <<<HTML
<form data-ftid="form_for_layout_renderer_test" data-name="form__form-for-layout-renderer-test" action="test.php" method="post">
<input type="hidden" name="_method" value="PATCH"/>
HTML;
        // @codingStandardsIgnoreEnd

        return $expected;
    }
}
