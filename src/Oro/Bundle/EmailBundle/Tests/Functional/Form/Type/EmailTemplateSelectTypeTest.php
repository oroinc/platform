<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateSelectType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class EmailTemplateSelectTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);
    }

    public function testGetParent(): void
    {
        $formType = new EmailTemplateSelectType();

        self::assertEquals(Select2TranslatableEntityType::class, $formType->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        $formType = new EmailTemplateSelectType();

        self::assertEquals('oro_email_template_list', $formType->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $form = self::createForm(EmailTemplateSelectType::class, null, [
            'data_route' => 'oro_api_get_emailtemplates',
            'depends_on_parent_field' => 'entityName',
        ]);

        self::assertFormOptions($form, [
            'label' => null,
            'class' => EmailTemplate::class,
            'choice_label' => 'name',
            'query_builder' => null,
            'depends_on_parent_field' => 'entityName',
            'target_field' => null,
            'selectedEntity' => null,
            'placeholder' => '',
            'empty_data' => null,
            'required' => true,
            'data_route' => 'oro_api_get_emailtemplates',
            'data_route_parameter' => 'entityName',
            'include_non_entity_templates' => true,
            'include_system_templates' => true,
            'choices' => [],
        ]);
    }

    public function testFinishViewSetsViewVariables(): void
    {
        $form = self::createForm(EmailTemplateSelectType::class, null, [
            'data_route' => 'oro_api_get_emailtemplates',
            'depends_on_parent_field' => 'entityName',
            'data_route_parameter' => 'entityName',
            'include_non_entity_templates' => true,
            'include_system_templates' => false,
        ]);

        $view = $form->createView();

        self::assertEquals('entityName', $view->vars['depends_on_parent_field']);
        self::assertEquals('oro_api_get_emailtemplates', $view->vars['data_route']);
        self::assertEquals('entityName', $view->vars['data_route_parameter']);
        self::assertTrue($view->vars['includeNonEntity']);
        self::assertFalse($view->vars['includeSystemTemplates']);
    }

    public function testFinishViewWithDependeeField(): void
    {
        $parentForm = self::createForm();
        $parentForm->add('entityName', TextType::class);
        $parentForm->add('template', EmailTemplateSelectType::class, [
            'data_route' => 'oro_api_get_emailtemplates',
            'depends_on_parent_field' => 'entityName',
        ]);

        $formView = $parentForm->createView();
        $entityNameFormView = $formView->children['entityName'];
        $templateFormView = $formView->children['template'];

        self::assertEquals('entityName', $templateFormView->vars['depends_on_parent_field']);
        self::assertArrayHasKey('dependee_field_id', $templateFormView->vars);
        self::assertEquals($entityNameFormView->vars['id'], $templateFormView->vars['dependee_field_id']);
    }

    public function testFinishViewWithNestedDependeeField(): void
    {
        $rootForm = self::createForm();
        $rootForm->add('entityName', TextType::class);
        $nestedForm = $rootForm->add('nested', FormType::class);
        $nestedForm->add('template', EmailTemplateSelectType::class, [
            'data_route' => 'oro_api_get_emailtemplates',
            'depends_on_parent_field' => 'entityName',
        ]);

        $formView = $rootForm->createView();
        $entityNameFormView = $formView->children['entityName'];
        $templateFormView = $formView->children['template'];

        self::assertEquals('entityName', $templateFormView->vars['depends_on_parent_field']);
        self::assertArrayHasKey('dependee_field_id', $templateFormView->vars);
        self::assertEquals($entityNameFormView->vars['id'], $templateFormView->vars['dependee_field_id']);
    }

    public function testFinishViewWithoutDependeeField(): void
    {
        $parentForm = self::createForm();
        $parentForm->add('template', EmailTemplateSelectType::class, [
            'data_route' => 'oro_api_get_emailtemplates',
            'depends_on_parent_field' => 'nonExistentField',
        ]);

        $formView = $parentForm->createView();
        $templateFormView = $formView->children['template'];

        self::assertEquals('nonExistentField', $templateFormView->vars['depends_on_parent_field']);
        self::assertArrayNotHasKey('dependee_field_id', $templateFormView->vars);
    }

    public function testDefaultConfigs(): void
    {
        $form = self::createForm(EmailTemplateSelectType::class, null, [
            'data_route' => 'oro_api_get_emailtemplates',
            'depends_on_parent_field' => 'entityName',
        ]);

        $config = $form->getConfig();
        $configs = $config->getOption('configs');

        self::assertArrayHasKey('placeholder', $configs);
        self::assertEquals('oro.email.form.choose_template', $configs['placeholder']);
    }

    public function testConfigsNormalizer(): void
    {
        $customConfigs = [
            'placeholder' => 'custom.placeholder',
            'custom_option' => 'custom_value',
        ];

        $form = self::createForm(EmailTemplateSelectType::class, null, [
            'data_route' => 'oro_api_get_emailtemplates',
            'depends_on_parent_field' => 'entityName',
            'configs' => $customConfigs,
        ]);

        $config = $form->getConfig();
        $configs = $config->getOption('configs');

        self::assertEquals('custom.placeholder', $configs['placeholder']);
        self::assertEquals('custom_value', $configs['custom_option']);
    }

    public function testChoicesWithoutSelectedEntity(): void
    {
        $form = self::createForm(EmailTemplateSelectType::class, null, [
            'data_route' => 'oro_api_get_emailtemplates',
            'depends_on_parent_field' => 'entityName',
            'selectedEntity' => null,
        ]);

        $config = $form->getConfig();
        $choices = $config->getOption('choices');

        if (is_callable($choices)) {
            $resolvedChoices = $choices($config->getOptions());
            self::assertEquals([], $resolvedChoices);
        }
    }

    public function testChoicesWithSelectedEntity(): void
    {
        $form = self::createForm(EmailTemplateSelectType::class, null, [
            'data_route' => 'oro_api_get_emailtemplates',
            'depends_on_parent_field' => 'entityName',
            'selectedEntity' => 'User',
        ]);

        $config = $form->getConfig();
        $choices = $config->getOption('choices');

        if (is_callable($choices)) {
            $resolvedChoices = $choices($config->getOptions());
            self::assertNull($resolvedChoices);
        }
    }

    public function testFormSubmit(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $emailTemplate = $entityManager->getRepository(EmailTemplate::class)
            ->findOneBy(['name' => 'user_reset_password']);

        $form = self::createForm();
        $form->add('template', EmailTemplateSelectType::class, ['choices' => [$emailTemplate]]);

        $form->submit(['template' => $emailTemplate->getId()]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());

        self::assertSame($emailTemplate, $form->getData()['template']);
    }
}
