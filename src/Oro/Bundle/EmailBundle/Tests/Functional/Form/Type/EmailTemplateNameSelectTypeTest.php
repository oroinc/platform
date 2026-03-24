<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateNameSelectType;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateData;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormView;

final class EmailTemplateNameSelectTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);
        $this->loadFixtures([LoadEmailTemplateData::class]);
    }

    public function testGetBlockPrefix(): void
    {
        $form = self::createForm(EmailTemplateNameSelectType::class, null, [
            'entity_name' => LoadEmailTemplateData::ENTITY_NAME,
        ]);

        self::assertContains(
            'oro_email_template_name_select',
            $form->createView()->vars['block_prefixes']
        );
    }

    public function testGetParent(): void
    {
        $form = self::createForm(EmailTemplateNameSelectType::class, null, [
            'entity_name' => LoadEmailTemplateData::ENTITY_NAME,
        ]);

        self::assertInstanceOf(
            Select2ChoiceType::class,
            $form->getConfig()->getType()->getParent()->getInnerType()
        );
    }

    public function testEntityNameOptionDefaultsToNull(): void
    {
        $form = self::createForm(EmailTemplateNameSelectType::class);

        self::assertNull($form->getConfig()->getOption('entity_name'));
    }

    public function testSubmitWithValidTemplateName(): void
    {
        $form = self::createForm(EmailTemplateNameSelectType::class, null, [
            'entity_name' => LoadEmailTemplateData::ENTITY_NAME,
        ]);
        $form->submit('no_system');

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertSame('no_system', $form->getData());
    }

    public function testSubmitWithNonExistingTemplateName(): void
    {
        $form = self::createForm(EmailTemplateNameSelectType::class, null, [
            'entity_name' => LoadEmailTemplateData::ENTITY_NAME,
        ]);
        $form->submit('non_existing_template');

        self::assertFalse($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function testChoicesContainOnlyTemplatesForSpecifiedEntity(): void
    {
        $form = self::createForm(EmailTemplateNameSelectType::class, null, [
            'entity_name' => LoadEmailTemplateData::ENTITY_NAME,
        ]);
        $choiceValues = $this->getChoiceValues($form->createView());

        // All four fixture templates for Entity\Name should be present
        self::assertContains('no_system', $choiceValues);
        self::assertContains('system', $choiceValues);
        self::assertContains('system_not_visible', $choiceValues);
        self::assertContains('not_system_not_visible', $choiceValues);

        // Templates from other entities should not appear
        self::assertNotContains('test_template', $choiceValues);
        self::assertNotContains('no_entity_name', $choiceValues);
        self::assertNotContains('no_system_no_entity', $choiceValues);
    }

    public function testChoicesForNullEntityNameExcludeEntitySpecificTemplates(): void
    {
        $form = self::createForm(EmailTemplateNameSelectType::class, null, [
            'entity_name' => null,
        ]);
        $choiceValues = $this->getChoiceValues($form->createView());

        // Null-entity fixture templates should be present
        self::assertContains('no_entity_name', $choiceValues);
        self::assertContains('no_system_no_entity', $choiceValues);
        self::assertContains('system_fail_to_compile', $choiceValues);

        // Entity-specific fixture templates should not appear
        self::assertNotContains('no_system', $choiceValues);
        self::assertNotContains('system', $choiceValues);
        self::assertNotContains('system_not_visible', $choiceValues);
        self::assertNotContains('not_system_not_visible', $choiceValues);
    }

    public function testChoicesAreEmptyForUnknownEntityName(): void
    {
        $form = self::createForm(EmailTemplateNameSelectType::class, null, [
            'entity_name' => 'NonExistent\Entity',
        ]);

        self::assertEmpty($this->getChoiceValues($form->createView()));
    }

    private function getChoiceValues(FormView $formView): array
    {
        return \array_map(static fn ($choice) => $choice->value, $formView->vars['choices']);
    }
}
