<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Twig\SecurityPolicy;

use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\Twig\SecurityPolicy\LoadEmailTemplateSecurityPolicyCheckerData;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\EmailTemplateSecurityPolicyChecker;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFilterViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFunctionViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyMethodViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyPropertyViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyTagViolation;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for EmailTemplateSecurityPolicyChecker.
 *
 * Covers:
 * - No-violation paths (allowed elements, entity getter methods, null fields, unknown template name).
 * - Each violation type: tag, filter, function, property, method.
 * - Violation collection across both default template fields (subject + content).
 * - Multiple violations within the same field.
 * - Customising the checked fields via setEmailTemplateFields().
 * - Error path: field name without a corresponding getter.
 *
 * The sandbox security policy for email templates allows:
 * - Tags    : app, for, if, apply, set, block.
 * - Filters : default, date, escape, format, length, lower, nl2br, number_format,
 *             title, trim, upper, oro_html_sanitize, slice, spaceless.
 * - Functions: _entity_var, date.
 *
 * Entity/method resolution:
 * - The TestEntityVariablesProvider (services_test.yml) registers EmailTemplateModel with
 *   PROPERTIES = ['subject'] and METHODS = ['getSubject']. All other properties/methods on
 *   that class are therefore disallowed by the sandbox security policy, making the
 *   property- and method-violation assertions fully deterministic.
 * - Entity methods whose names start with get/is/has on actual Doctrine entities are allowed
 *   by {@see EmailTemplateSecurityPolicy} regardless of the above config, hence the use of the
 *   non-entity EmailTemplateModel class for violation tests.
 */
final class EmailTemplateSecurityPolicyCheckerTest extends WebTestCase
{
    private EmailTemplateSecurityPolicyChecker $checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailTemplateSecurityPolicyCheckerData::class]);
        $this->checker = self::getContainer()->get('oro_email.twig.security_policy.email_template_checker');
        // Reset to default fields after each test to prevent contamination from tests
        // that call setEmailTemplateFields(), because the checker is a container singleton.
        $this->checker->setEmailTemplateFields(['subject', 'content']);
    }

    // ------------------------------------------------------------------
    // Happy-path tests – no violations expected
    // ------------------------------------------------------------------

    public function testCheckSecurityPolicyReturnsNoViolationsForCleanTemplateWithNullName(): void
    {
        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setSubject('{% if true %}Hello{% endif %}');
        $template->setContent('{{ "world"|upper }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertSame([], $violations);
    }

    public function testCheckSecurityPolicyReturnsNoViolationsForTemplateWithAllowedElements(): void
    {
        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setSubject('{% for i in [1, 2, 3] %}{{ i }}{% endfor %}');
        $template->setContent('{{ date("Y") }} {{ "hello"|trim|lower }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertSame([], $violations);
    }

    public function testCheckSecurityPolicyReturnsNoViolationsForNullSubjectAndContent(): void
    {
        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setSubject(null);
        $template->setContent(null);

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertSame([], $violations);
    }

    public function testCheckSecurityPolicyReturnsNoViolationsWhenTemplateNameNotFoundInDatabase(): void
    {
        // Template name not present in DB -> no entity class resolved -> property/method
        // access checks are skipped; sandbox compile checks still run but the content is clean.
        $template = new EmailTemplateModel();
        $template->setName('nonexistent_template_for_security_check');
        $template->setSubject('{{ entity.subject }}');
        $template->setContent('{{ entity.content }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertSame([], $violations);
    }

    public function testCheckSecurityPolicyResolvesEntityClassFromModelEntityNameWithoutDatabaseLookup(): void
    {
        // When entityName is set directly on the model (e.g., during form validation of a new template
        // that has not been persisted yet), the entity class is resolved from the model property
        // without consulting the metadata provider.
        $template = new EmailTemplateModel();
        $template->setName('nonexistent_template_for_security_check');
        $template->setEntityName(EmailTemplateModel::class);
        $template->setSubject('');
        $template->setContent('{{ entity.content }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        // 'content' is NOT in PROPERTIES[EmailTemplateModel] — only 'subject' is registered
        // by TestEntityVariablesProvider, so accessing 'content' triggers a property violation.
        self::assertCount(1, $violations);

        $violation = $violations[0];
        self::assertInstanceOf(EmailTemplateSecurityPolicyPropertyViolation::class, $violation);
        self::assertSame('content', $violation->getName());
        self::assertSame('entity', $violation->getVariableName());
        self::assertSame(EmailTemplateModel::class, $violation->getEntityClass());
        self::assertSame('content', $violation->getTemplateField());
    }

    public function testCheckSecurityPolicyReturnsNoViolationsWhenModelEntityNameSetWithAllowedProperty(): void
    {
        // 'subject' is in PROPERTIES[EmailTemplateModel] via TestEntityVariablesProvider.
        // Entity class is resolved from the model property directly, without a database lookup.
        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setEntityName(EmailTemplateModel::class);
        $template->setSubject('');
        $template->setContent('{{ entity.subject }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertSame([], $violations);
    }

    public function testCheckSecurityPolicyReturnsNoViolationsForAllowedEntityPropertyAccess(): void
    {
        // 'subject' is in PROPERTIES[EmailTemplateModel] via TestEntityVariablesProvider.
        $template = new EmailTemplateModel();
        $template->setName(LoadEmailTemplateSecurityPolicyCheckerData::TEMPLATE_WITH_MODEL_ENTITY_CLASS_NAME);
        $template->setSubject('');
        $template->setContent('{{ entity.subject }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertSame([], $violations);
    }

    public function testCheckSecurityPolicyReturnsNoViolationsForAllowedEntityGetterMethod(): void
    {
        // 'getSubject' is in METHODS[EmailTemplateModel] via TestEntityVariablesProvider.
        $template = new EmailTemplateModel();
        $template->setName(LoadEmailTemplateSecurityPolicyCheckerData::TEMPLATE_WITH_MODEL_ENTITY_CLASS_NAME);
        $template->setSubject('');
        $template->setContent('{{ entity.getSubject() }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertSame([], $violations);
    }

    // ------------------------------------------------------------------
    // Sandbox compile-time violations (tag / filter / function)
    // ------------------------------------------------------------------

    public function testCheckSecurityPolicyReturnsTagViolationForDisallowedTagInContent(): void
    {
        // 'autoescape' is not in the allowed tags list for the email Twig environment.
        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setSubject('Clean subject');
        $template->setContent('{% autoescape %}protected{% endautoescape %}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertCount(1, $violations);

        $violation = $violations[0];
        self::assertInstanceOf(EmailTemplateSecurityPolicyTagViolation::class, $violation);
        self::assertSame('autoescape', $violation->getName());
        self::assertSame('content', $violation->getTemplateField());
        self::assertNull($violation->getVariableName());
        self::assertNull($violation->getEntityClass());
    }

    public function testCheckSecurityPolicyReturnsFilterViolationForDisallowedFilterInSubject(): void
    {
        // 'raw' is not in the allowed filters list for the email Twig environment.
        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setSubject('{{ "hello"|raw }}');
        $template->setContent('Clean content');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertCount(1, $violations);

        $violation = $violations[0];
        self::assertInstanceOf(EmailTemplateSecurityPolicyFilterViolation::class, $violation);
        self::assertSame('raw', $violation->getName());
        self::assertSame('subject', $violation->getTemplateField());
        self::assertNull($violation->getVariableName());
        self::assertNull($violation->getEntityClass());
    }

    public function testCheckSecurityPolicyReturnsFunctionViolationForDisallowedFunctionInContent(): void
    {
        // 'constant' (Twig CoreExtension) is not in the allowed functions list.
        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setSubject('Clean subject');
        $template->setContent('{{ constant("PHP_EOL") }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertCount(1, $violations);

        $violation = $violations[0];
        self::assertInstanceOf(EmailTemplateSecurityPolicyFunctionViolation::class, $violation);
        self::assertSame('constant', $violation->getName());
        self::assertSame('content', $violation->getTemplateField());
        self::assertNull($violation->getVariableName());
        self::assertNull($violation->getEntityClass());
    }

    // ------------------------------------------------------------------
    // Static-analysis violations (entity property / method access)
    // ------------------------------------------------------------------

    public function testCheckSecurityPolicyReturnsPropertyViolationForDisallowedPropertyReadOnEntityFromDatabase(): void
    {
        // 'content' is NOT in PROPERTIES[EmailTemplateModel] — only 'subject' is registered
        // by TestEntityVariablesProvider, so accessing 'content' triggers a property violation.
        $template = new EmailTemplateModel();
        $template->setName(LoadEmailTemplateSecurityPolicyCheckerData::TEMPLATE_WITH_MODEL_ENTITY_CLASS_NAME);
        $template->setSubject('');
        $template->setContent('{{ entity.content }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertCount(1, $violations);

        $violation = $violations[0];
        self::assertInstanceOf(EmailTemplateSecurityPolicyPropertyViolation::class, $violation);
        self::assertSame('content', $violation->getName());
        self::assertSame('entity', $violation->getVariableName());
        self::assertSame(EmailTemplateModel::class, $violation->getEntityClass());
        self::assertSame('content', $violation->getTemplateField());
        self::assertGreaterThanOrEqual(1, $violation->getTemplateLine());
    }

    public function testCheckSecurityPolicyReturnsMethodViolationForDisallowedMethodCallOnEntityFromDatabase(): void
    {
        // 'getContent' is NOT in METHODS[EmailTemplateModel] — only 'getSubject' is registered,
        // so calling getContent() triggers a method violation.
        $template = new EmailTemplateModel();
        $template->setName(LoadEmailTemplateSecurityPolicyCheckerData::TEMPLATE_WITH_MODEL_ENTITY_CLASS_NAME);
        $template->setSubject('');
        $template->setContent('{{ entity.getContent() }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertCount(1, $violations);

        $violation = $violations[0];
        self::assertInstanceOf(EmailTemplateSecurityPolicyMethodViolation::class, $violation);
        self::assertSame('getContent', $violation->getName());
        self::assertSame('entity', $violation->getVariableName());
        self::assertSame(EmailTemplateModel::class, $violation->getEntityClass());
        self::assertSame('content', $violation->getTemplateField());
        self::assertGreaterThanOrEqual(1, $violation->getTemplateLine());
    }

    // ------------------------------------------------------------------
    // Multi-field and multi-violation collection
    // ------------------------------------------------------------------

    public function testCheckSecurityPolicyCollectsViolationsFromBothSubjectAndContentFields(): void
    {
        // Both default fields are checked independently; each produces its own violation type.
        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setSubject('{{ "test"|raw }}');
        $template->setContent('{{ constant("PHP_EOL") }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertCount(2, $violations);

        $subjectViolation = $violations[0];
        self::assertInstanceOf(EmailTemplateSecurityPolicyFilterViolation::class, $subjectViolation);
        self::assertSame('raw', $subjectViolation->getName());
        self::assertSame('subject', $subjectViolation->getTemplateField());

        $contentViolation = $violations[1];
        self::assertInstanceOf(EmailTemplateSecurityPolicyFunctionViolation::class, $contentViolation);
        self::assertSame('constant', $contentViolation->getName());
        self::assertSame('content', $contentViolation->getTemplateField());
    }

    public function testCheckSecurityPolicyCollectsMultiplePropertyViolationsFromSameField(): void
    {
        // Both 'content' and 'name' are not in PROPERTIES[EmailTemplateModel], so each access
        // is reported as a separate property violation within the same template field.
        $template = new EmailTemplateModel();
        $template->setName(LoadEmailTemplateSecurityPolicyCheckerData::TEMPLATE_WITH_MODEL_ENTITY_CLASS_NAME);
        $template->setSubject('');
        $template->setContent('{{ entity.content }} {{ entity.name }}');

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertCount(2, $violations);
        self::assertContainsOnlyInstancesOf(EmailTemplateSecurityPolicyPropertyViolation::class, $violations);

        self::assertSame('content', $violations[0]->getName());
        self::assertSame('content', $violations[0]->getTemplateField());

        self::assertSame('name', $violations[1]->getName());
        self::assertSame('content', $violations[1]->getTemplateField());
    }

    // ------------------------------------------------------------------
    // setEmailTemplateFields() customisation
    // ------------------------------------------------------------------

    public function testCheckSecurityPolicyChecksOnlyConfiguredFieldsWhenCustomFieldsSet(): void
    {
        // Only the 'subject' field is configured — the 'content' violation must not appear.
        $template = new EmailTemplateModel();
        $template->setName(null);
        $template->setSubject('{{ "hi"|raw }}');
        $template->setContent('{{ constant("PHP_EOL") }}');

        $this->checker->setEmailTemplateFields(['subject']);

        $violations = $this->checker->checkSecurityPolicy($template);

        self::assertCount(1, $violations);
        self::assertInstanceOf(EmailTemplateSecurityPolicyFilterViolation::class, $violations[0]);
        self::assertSame('raw', $violations[0]->getName());
        self::assertSame('subject', $violations[0]->getTemplateField());
    }

    // ------------------------------------------------------------------
    // Error path
    // ------------------------------------------------------------------

    public function testCheckSecurityPolicyThrowsExceptionWhenConfiguredFieldHasNoGetterOnEmailTemplate(): void
    {
        $template = new EmailTemplateModel();
        $template->setName(null);

        $this->checker->setEmailTemplateFields(['nonExistentField']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Email template does not have a getter for field "nonExistentField"/');
        $this->expectExceptionMessageMatches('/getNonExistentField\(\)/');

        $this->checker->checkSecurityPolicy($template);
    }
}
