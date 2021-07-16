<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class EntityRouteVariableProcessorTest extends WebTestCase
{
    /** @var EmailRenderer */
    private $emailRenderer;

    /** @var Item */
    private $entity;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->emailRenderer = self::getContainer()->get('oro_email.email_renderer');
        $this->loadFixtures([LoadUser::class]);
        $this->entity = $this->createItemEntity();
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

        $this->assertEquals(1, preg_match($expected, $data), 'data: ' . $data);
    }

    public function variablesDataProvider(): array
    {
        $baseUrl = '(http|https)\:\/\/.*\/';

        return [
            'index'      => [
                'variable' => 'entity.url.index',
                'expected' => sprintf('/^%s%s$/i', $baseUrl, 'test\/item\/')
            ],
            'view'       => [
                'variable' => 'entity.url.view',
                'expected' => sprintf('/^%s%s\d+$/i', $baseUrl, 'test\/item\/view\/')
            ],
            'create'     => [
                'variable' => 'entity.url.create',
                'expected' => sprintf('/^%s%s$/i', $baseUrl, 'test\/item\/create')
            ],
            'update'     => [
                'variable' => 'entity.url.update',
                'expected' => sprintf('/^%s%s\d+$/i', $baseUrl, 'test\/item\/update\/')
            ],
            'view.child' => [
                'variable' => 'entity.owner.url.view',
                'expected' => sprintf('/^%s%s\d+$/i', $baseUrl, 'user\/view\/')
            ]
        ];
    }

    private function createItemEntity(): Item
    {
        $testEntity = new Item();
        $testEntity->owner = $this->getReference('user');

        $em = $this->getEntityManager(get_class($testEntity));
        $em->persist($testEntity);
        $em->flush();

        return $testEntity;
    }

    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass($entityClass);
    }
}
