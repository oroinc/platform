<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @see \Oro\Bundle\EmailBundle\Tests\Functional\Environment\TestEntityVariablesProvider
 * @see \Oro\Bundle\EmailBundle\Tests\Functional\Environment\TestVariableProcessor
 */
class EmailRendererTest extends WebTestCase
{
    /** @var EmailRenderer */
    private $emailRenderer;

    /** @var Item */
    private $entity;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->emailRenderer = self::getContainer()->get('oro_email.email_renderer');
        $this->entity = $this->createTestEntity();
    }

    /**
     * @dataProvider variablesDataProvider
     */
    public function testVariables($variable, $expected)
    {
        $data = $this->emailRenderer->renderTemplate(
            sprintf('{{ %s }}', $variable),
            ['entity' => $this->entity]
        );

        $this->assertEquals($expected, $data);
    }

    public function variablesDataProvider(): array
    {
        return [
            'root entity'                                                    => [
                'variable' => 'entity',
                'expected' => 'test'
            ],
            'field for root entity'                                          => [
                'variable' => 'entity.description',
                'expected' => 'test'
            ],
            'undefined field for root entity'                                => [
                'variable' => 'entity.undefined',
                'expected' => 'N/A'
            ],
            'child entity'                                                   => [
                'variable' => 'entity.organization',
                'expected' => 'Test Organization'
            ],
            'field for child entity'                                         => [
                'variable' => 'entity.organization.name',
                'expected' => 'Test Organization'
            ],
            'undefined field for child entity'                               => [
                'variable' => 'entity.organization.undefined',
                'expected' => 'N/A'
            ],
            'null child entity'                                              => [
                'variable' => 'entity.owner',
                'expected' => ''
            ],
            'field for null child entity'                                    => [
                'variable' => 'entity.owner.firstName',
                'expected' => 'N/A'
            ],
            'undefined field for null child entity'                          => [
                'variable' => 'entity.owner.undefined',
                'expected' => 'N/A'
            ],
            'computed variable (array)'                                      => [
                'variable' => 'entity.organization.computedArray.testProperty1',
                'expected' => 'testProperty1 value'
            ],
            'undefined field for computed variable (array)'                  => [
                'variable' => 'entity.organization.computedArray.testProperty1.undefined',
                'expected' => 'N/A'
            ],
            'computed variable (multidimensional array, 2nd level)'          => [
                'variable' => 'entity.organization.computedArray.testProperty2.attribute1',
                'expected' => 'testProperty2.attribute1 value'
            ],
            'computed variable (multidimensional array, 3rd level)'          => [
                'variable' => 'entity.organization.computedArray.testProperty2.attribute2.attribute21',
                'expected' => 'testProperty2.attribute2.attribute21 value'
            ],
            'undefined field for computed variable (multidimensional array)' => [
                'variable' => 'entity.organization.computedArray.testProperty2.attribute2.undefined',
                'expected' => 'N/A'
            ],
            'computed variable (object)'                                     => [
                'variable' => 'entity.organization.computedObject.subject',
                'expected' => 'test subject'
            ],
            'undefined field for computed variable (object)'                 => [
                'variable' => 'entity.organization.computedObject.undefined',
                'expected' => 'N/A'
            ],
            'nested computed variable'                                       => [
                'variable' => 'entity.organization.computedObject.computedOrg',
                'expected' => 'EmailTemplate Organization'
            ],
            'field for nested computed variable'                             => [
                'variable' => 'entity.organization.computedObject.computedOrg.description',
                'expected' => 'EmailTemplate Organization Description'
            ],
            'undefined field for nested computed variable'                   => [
                'variable' => 'entity.organization.computedObject.computedOrg.undefined',
                'expected' => 'N/A'
            ],
            'object inside computed array variable'                          => [
                'variable' => 'entity.organization.computedArray.testProperty2.object1.subject',
                'expected' => 'testProperty2.object1 subject'
            ]
        ];
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
