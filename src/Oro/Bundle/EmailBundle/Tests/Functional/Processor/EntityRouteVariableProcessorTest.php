<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Processor;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityRouteVariableProcessorTest extends WebTestCase
{
    /** @var EmailRenderer */
    protected $emailRenderer;

    /** @var Item */
    protected $entity;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->emailRenderer = $this->getContainer()->get('oro_email.email_renderer');
        $this->entity = $this->createItemEntity();
    }

    /**
     * @param $variable
     * @param $expected
     *
     * @dataProvider variablesDataProvider
     */
    public function testVariables($variable, $expected)
    {
        $data = $this->emailRenderer->renderWithDefaultFilters(
            sprintf('{{ %s }}', $variable),
            ['entity' => $this->entity]
        );

        $this->assertEquals(1, preg_match($expected, $data));
    }

    /**
     * @return \Generator
     */
    public function variablesDataProvider()
    {
        $baseUrl = '(http|https)\:\/\/.*\/';

        yield 'index' => [
            'variable' => 'entity.url.index',
            'expected' => sprintf('/^%s%s$/i', $baseUrl, 'test\/item\/'),
        ];

        yield 'view' => [
            'variable' => 'entity.url.view',
            'expected' => sprintf('/^%s%s\d+$/i', $baseUrl, 'test\/item\/view\/'),
        ];

        yield 'create' => [
            'variable' => 'entity.url.create',
            'expected' => sprintf('/^%s%s$/i', $baseUrl, 'test\/item\/create'),
        ];

        yield 'update' => [
            'variable' => 'entity.url.update',
            'expected' => sprintf('/^%s%s\d+$/i', $baseUrl, 'test\/item\/update\/'),
        ];
    }

    /**
     * @return Item
     */
    protected function createItemEntity()
    {
        $testEntity = new Item();
        $this->getManager(Item::class)->persist($testEntity);
        $this->getManager(Item::class)->flush($testEntity);

        return $testEntity;
    }

    /**
     * @param $class
     *
     * @return EntityManager|null|object
     */
    private function getManager($class)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($class);
    }
}
