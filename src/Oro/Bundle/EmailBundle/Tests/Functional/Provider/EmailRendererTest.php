<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Twig\Error\RuntimeError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTagError;

/**
 * @dbIsolationPerTest
 * @see \Oro\Bundle\EmailBundle\Tests\Functional\Environment\TestEntityVariablesProvider
 * @see \Oro\Bundle\EmailBundle\Tests\Functional\Environment\TestVariableProcessor
 */
class EmailRendererTest extends WebTestCase
{
    use RolePermissionExtension;

    private EmailRenderer $emailRenderer;

    private TestActivity $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->emailRenderer = self::getContainer()->get('oro_email.email_renderer');
        $this->entity = $this->createTestEntity();

        self::getContainer()->get('security.token_storage')->setToken(null);
        self::getContainer()->get('oro_email.twig.email_template_entity_access_checker')->reset();
    }

    /**
     * @dataProvider variablesDataProvider
     */
    public function testVariables($variable, $expected): void
    {
        $data = $this->emailRenderer->renderTemplate(
            sprintf('{{ %s }}', $variable),
            ['entity' => $this->entity]
        );

        self::assertEquals($expected, $data);
    }

    public function variablesDataProvider(): array
    {
        return [
            'root entity' => [
                'variable' => 'entity',
                'expected' => 'test',
            ],
            'field for root entity' => [
                'variable' => 'entity.description',
                'expected' => 'test',
            ],
            'undefined field for root entity' => [
                'variable' => 'entity.undefined',
                'expected' => 'N/A',
            ],
            'child entity' => [
                'variable' => 'entity.organization',
                'expected' => 'Test Organization',
            ],
            'field for child entity' => [
                'variable' => 'entity.organization.name',
                'expected' => 'Test Organization',
            ],
            'undefined field for child entity' => [
                'variable' => 'entity.organization.undefined',
                'expected' => 'N/A',
            ],
            'null child entity' => [
                'variable' => 'entity.owner',
                'expected' => '',
            ],
            'field for null child entity' => [
                'variable' => 'entity.owner.firstName',
                'expected' => 'N/A',
            ],
            'undefined field for null child entity' => [
                'variable' => 'entity.owner.undefined',
                'expected' => 'N/A',
            ],
            'computed variable (array)' => [
                'variable' => 'entity.organization.computedArray.testProperty1',
                'expected' => 'testProperty1 value',
            ],
            'undefined field for computed variable (array)' => [
                'variable' => 'entity.organization.computedArray.testProperty1.undefined',
                'expected' => 'N/A',
            ],
            'computed variable (multidimensional array, 2nd level)' => [
                'variable' => 'entity.organization.computedArray.testProperty2.attribute1',
                'expected' => 'testProperty2.attribute1 value',
            ],
            'computed variable (multidimensional array, 3rd level)' => [
                'variable' => 'entity.organization.computedArray.testProperty2.attribute2.attribute21',
                'expected' => 'testProperty2.attribute2.attribute21 value',
            ],
            'undefined field for computed variable (multidimensional array)' => [
                'variable' => 'entity.organization.computedArray.testProperty2.attribute2.undefined',
                'expected' => 'N/A',
            ],
            'computed variable (object)' => [
                'variable' => 'entity.organization.computedObject.subject',
                'expected' => 'test subject',
            ],
            'undefined field for computed variable (object)' => [
                'variable' => 'entity.organization.computedObject.undefined',
                'expected' => 'N/A',
            ],
            'nested computed variable' => [
                'variable' => 'entity.organization.computedObject.computedOrg',
                'expected' => 'EmailTemplate Organization',
            ],
            'field for nested computed variable' => [
                'variable' => 'entity.organization.computedObject.computedOrg.description',
                'expected' => 'EmailTemplate Organization Description',
            ],
            'undefined field for nested computed variable' => [
                'variable' => 'entity.organization.computedObject.computedOrg.undefined',
                'expected' => 'N/A',
            ],
            'object inside computed array variable' => [
                'variable' => 'entity.organization.computedArray.testProperty2.object1.subject',
                'expected' => 'testProperty2.object1 subject',
            ],
        ];
    }

    public function testRenderTemplateWithNotAllowedFunctionThrowsException(): void
    {
        $this->expectException(SecurityNotAllowedFunctionError::class);

        $this->emailRenderer->renderTemplate('{{ oro_config_value(\'oro_user.password_min_length\') }}');
    }

    public function testRenderTemplateWithNotAllowedTagThrowsException(): void
    {
        $this->expectException(SecurityNotAllowedTagError::class);

        $this->emailRenderer->renderTemplate('{% macro test() %}foobar{% endmacro %}');
    }

    public function testRenderTemplateWithNotAllowedFilterThrowsException(): void
    {
        $this->expectException(SecurityNotAllowedFilterError::class);

        $this->emailRenderer->renderTemplate('{{ foobar|raw }}');
    }

    public function testRenderTemplateWithNotAllowedMethodReplacesWithNull(): void
    {
        $this->loadFixtures([LoadUser::class]);
        $entity = $this->getReference(LoadUser::USER);

        self::assertSame(
            'N/A',
            $this->emailRenderer->renderTemplate(
                '{{ entity.getPassword()|default(\'N/A\') }}',
                ['entity' => $entity]
            )
        );
    }

    /**
     * The templates in this test use a "user" root rather than "entity", because an "entity"-rooted path
     * is rewritten into a variable processor call before rendering and so never reaches the Twig sandbox.
     *
     * @dataProvider notAllowedPropertyTemplateDataProvider
     *
     * @see \Oro\Bundle\EmailBundle\Twig\Node\SafeGetAttrNode
     */
    public function testRenderTemplateWithNotAllowedPropertyRendersNothing(
        string $template,
        string $expected
    ): void {
        $this->loadFixtures([LoadUser::class]);
        $entity = $this->getReference(LoadUser::USER);

        self::assertSame($expected, $this->emailRenderer->renderTemplate($template, ['user' => $entity]));
    }

    /**
     * The templates in this test use a "user" root rather than "entity", because an "entity"-rooted path
     * is rewritten into a variable processor call before rendering and so never reaches the Twig sandbox.
     *
     * @dataProvider notAllowedPropertyTemplateDataProvider
     */
    public function testRenderTemplateWithNotAllowedPropertyUsesValueSubstitutedByListener(string $template): void
    {
        $this->loadFixtures([LoadUser::class]);
        $entity = $this->getReference(LoadUser::USER);

        $listener = static function (EmailTemplateSecurityPolicyViolationEvent $event) {
            $event->setValue('REDACTED ' . $event->getItem());
        };

        $eventDispatcher = self::getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(EmailTemplateSecurityPolicyViolationEvent::class, $listener);
        try {
            self::assertSame(
                '[REDACTED password]',
                $this->emailRenderer->renderTemplate($template, ['user' => $entity])
            );
        } finally {
            $eventDispatcher->removeListener(EmailTemplateSecurityPolicyViolationEvent::class, $listener);
        }
    }

    public function notAllowedPropertyTemplateDataProvider(): array
    {
        return [
            'read' => [
                'template' => '[{{ user.password }}]',
                'expected' => '[]',
            ],
            // The "default" filter compiles the attribute access into an is-defined test, which the denied
            // attribute answers with false unless a listener substitutes a value for it.
            'read with the default filter' => [
                'template' => '[{{ user.password|default(\'REDACTED password\') }}]',
                'expected' => '[REDACTED password]',
            ],
            'is defined test' => [
                'template' => '[{{ user.password is defined ? \'REDACTED password\' : \'\' }}]',
                'expected' => '[]',
            ],
        ];
    }

    public function testRenderTemplateWithNotAllowedPropertyReplacesWithNulll(): void
    {
        $this->loadFixtures([LoadUser::class]);
        $entity = $this->getReference(LoadUser::USER);

        self::assertSame(
            'N/A',
            $this->emailRenderer->renderTemplate(
                '{{ entity.password|default(\'N/A\') }}',
                ['entity' => $entity]
            )
        );
    }

    public function testRenderTemplateWithNotAllowedPropertyReplacesWithNullAndSurvivesInLoop(): void
    {
        $this->loadFixtures([LoadUser::class]);
        $entity = $this->getReference(LoadUser::USER);

        self::assertSame(
            'Text that must be rendered.',
            $this->emailRenderer->renderTemplate(
                <<<'TWIG'
{% for organization in entity.organizations %}
    {{ organization.name }} - must not be rendered at all.
{% endfor %}
Text that must be rendered.
TWIG,
                ['entity' => $entity]
            )
        );
    }
    public function testRenderTemplateWithNotAllowedMethodReplacesWithNullAndSurvivesInLoop(): void
    {
        $this->loadFixtures([LoadUser::class]);
        $entity = $this->getReference(LoadUser::USER);

        self::assertSame(
            'Text that must be rendered.',
            $this->emailRenderer->renderTemplate(
                <<<'TWIG'
{% for organization in entity.getOrganizations() %}
    {{ organization.name }} - must not be rendered at all.
{% endfor %}
Text that must be rendered.
TWIG,
                ['entity' => $entity]
            )
        );
    }

    public function testRenderTemplateWithNotAllowedMethodReplacesWithNullAndCausesError(): void
    {
        $this->loadFixtures([LoadUser::class]);
        $entity = $this->getReference(LoadUser::USER);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessageMatches('/Impossible to access a key \("0"\) on a null variable in/');

        $this->emailRenderer->renderTemplate(
            '{{ entity.getOrganizations()[0].name }}',
            ['entity' => $entity]
        );
    }

    public function testRenderTemplateWithNotAllowedPropertyReplacesWithNullAndCausesError(): void
    {
        $this->loadFixtures([LoadUser::class]);
        $entity = $this->getReference(LoadUser::USER);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessageMatches('/Impossible to access a key \("0"\) on a null variable in/');

        $this->emailRenderer->renderTemplate(
            '{{ entity.organizations[0].name }}',
            ['entity' => $entity]
        );
    }

    /**
     * The templates below use an "activity" root rather than "entity", because an "entity"-rooted path is
     * rewritten into an _entity_var() call before rendering and so never reaches the Twig sandbox.
     *
     * @see \Oro\Bundle\EmailBundle\Twig\EmailTemplateEntityAccessChecker
     */
    public function testRenderTemplateRendersFieldOfRelatedRecordAllowedToView(): void
    {
        $activity = $this->createTestEntityInOrganizationOf(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        self::assertSame(
            '[test][' . $activity->getOrganization()->getName() . ']',
            $this->emailRenderer->renderTemplate(
                '[{{ activity.description }}][{{ activity.organization.name }}]',
                ['activity' => $activity]
            )
        );
    }

    public function testRenderTemplateRedactsFieldOfRelatedRecordNotAllowedToView(): void
    {
        $activity = $this->createTestEntityInOrganizationOf(self::AUTH_USER);
        $this->updateRolePermission(User::ROLE_ADMINISTRATOR, Organization::class, AccessLevel::NONE_LEVEL);
        $this->updateUserSecurityToken(self::AUTH_USER);

        self::assertSame(
            '[test][]',
            $this->emailRenderer->renderTemplate(
                '[{{ activity.description }}][{{ activity.organization.name }}]',
                ['activity' => $activity]
            )
        );
    }

    /**
     * String coercion of a record is checked by Twig\Extension\SandboxExtension::ensureToStringAllowed(), which
     * sits outside {@see \Oro\Bundle\EmailBundle\Twig\Node\SafeGetAttrNode}. It is brought back under the
     * violation event by {@see \Oro\Bundle\EmailBundle\Twig\Node\SafeCheckToStringNode}, so a denial resolves
     * to the empty value rather than aborting the render.
     */
    public function testRenderTemplateRedactsStringCoercionOfRelatedRecordNotAllowedToView(): void
    {
        $activity = $this->createTestEntityInOrganizationOf(self::AUTH_USER);
        $organizationName = $activity->getOrganization()->getName();
        $this->updateRolePermission(User::ROLE_ADMINISTRATOR, Organization::class, AccessLevel::NONE_LEVEL);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $rendered = $this->emailRenderer->renderTemplate(
            '[{{ activity.organization }}]',
            ['activity' => $activity]
        );

        self::assertSame('[]', $rendered);
        self::assertStringNotContainsString($organizationName, $rendered);
    }

    public function testRenderTemplateUsesValueSubstitutedByListenerForDeniedStringCoercion(): void
    {
        $activity = $this->createTestEntityInOrganizationOf(self::AUTH_USER);
        $this->updateRolePermission(User::ROLE_ADMINISTRATOR, Organization::class, AccessLevel::NONE_LEVEL);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $listener = static function (EmailTemplateSecurityPolicyViolationEvent $event) {
            $event->setValue('REDACTED ' . $event->getItem());
        };

        $eventDispatcher = self::getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(EmailTemplateSecurityPolicyViolationEvent::class, $listener);
        try {
            self::assertSame(
                '[REDACTED __toString]',
                $this->emailRenderer->renderTemplate(
                    '[{{ activity.organization }}]',
                    ['activity' => $activity]
                )
            );
        } finally {
            $eventDispatcher->removeListener(EmailTemplateSecurityPolicyViolationEvent::class, $listener);
        }
    }

    public function testRenderTemplateWithoutSecurityTokenReadsRelatedRecordRegardlessOfPermissions(): void
    {
        $activity = $this->createTestEntityInOrganizationOf(self::AUTH_USER);
        $organizationName = $activity->getOrganization()->getName();
        $this->updateRolePermission(User::ROLE_ADMINISTRATOR, Organization::class, AccessLevel::NONE_LEVEL);

        self::assertSame(
            sprintf('[test][%s][%s]', $organizationName, $organizationName),
            $this->emailRenderer->renderTemplate(
                '[{{ activity.description }}][{{ activity.organization.name }}][{{ activity.organization }}]',
                ['activity' => $activity]
            )
        );
    }

    private function createTestEntityInOrganizationOf(string $userEmail): TestActivity
    {
        $em = $this->getEntityManager(TestActivity::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        $testEntity = new TestActivity();
        $testEntity->setMessage('test message');
        $testEntity->setDescription('test');
        $testEntity->setOrganization($user->getOrganization());
        $testEntity->setOwner($user);

        $em->persist($testEntity);
        $em->flush();

        return $testEntity;
    }

    private function createTestEntity(): TestActivity
    {
        $org = new Organization();
        $org->setName('Test Organization');
        $org->setEnabled(true);

        $testEntity = new TestActivity();
        $testEntity->setMessage('test message');
        $testEntity->setDescription('test');
        $testEntity->setOrganization($org);

        $em = $this->getEntityManager(get_class($testEntity));
        $em->persist($org);
        $em->persist($testEntity);
        $em->flush();

        return $testEntity;
    }

    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass($entityClass);
    }
}
