<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
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
    private ConfigManager $configManager;

    private EmailRenderer $emailRenderer;

    private TestActivity $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->emailRenderer = self::getContainer()->get('oro_email.email_renderer');
        $this->entity = $this->createTestEntity();

        $this->configManager = self::getContainer()->get('oro_entity_config.config_manager');

        $emailConfigProvider = $this->configManager->getProvider('email');
        $entityConfig = $emailConfigProvider->getConfig(User::class, 'organizations');
        $entityConfig->set('available_in_template', false);
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $emailConfigProvider = $this->configManager->getProvider('email');
        $entityConfig = $emailConfigProvider->getConfig(User::class, 'organizations');
        $entityConfig->set('available_in_template', true);
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();
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
