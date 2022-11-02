<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessDefinitionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessDefinition */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new ProcessDefinition();
    }

    /**
     * @param mixed $propertyName
     * @param mixed $testValue
     * @param mixed $defaultValue
     * @dataProvider setGetDataProvider
     */
    public function testSetGetEntity($propertyName, $testValue, $defaultValue = null)
    {
        $setter = 'set' . ucfirst($propertyName);
        $getter = (is_bool($testValue) ? 'is' : 'get') . ucfirst($propertyName);

        $this->assertSame($defaultValue, $this->entity->$getter());
        $this->assertSame($this->entity, $this->entity->$setter($testValue));
        $this->assertSame($testValue, $this->entity->$getter());
    }

    public function setGetDataProvider(): array
    {
        return [
            'name' => ['name', 'test'],
            'label' => ['label', 'Test Definition'],
            'enabled' => ['enabled', false, true],
            'actionsConfiguration' => ['actionsConfiguration', ['my' => 'configuration']],
            'relatedEntity' => ['relatedEntity', 'My\Entity'],
            'executionOrder' => ['executionOrder', 42, 0],
            'excludeDefinitions' => ['excludeDefinitions', ['test'], []],
            'createdAt' => ['createdAt', new \DateTime()],
            'updatedAt' => ['updatedAt', new \DateTime()],
        ];
    }

    public function testImport()
    {
        $importedEntity = new ProcessDefinition();
        $importedEntity->setName('my_name')
            ->setLabel('My Label')
            ->setEnabled(false)
            ->setRelatedEntity('My/Entity')
            ->setExecutionOrder(25)
            ->setExcludeDefinitions(['foo'])
            ->setActionsConfiguration(['key' => 'value']);

        $this->assertNotEquals($importedEntity->getName(), $this->entity->getName());
        $this->assertNotEquals($importedEntity->getLabel(), $this->entity->getLabel());
        $this->assertNotEquals($importedEntity->getRelatedEntity(), $this->entity->getRelatedEntity());
        $this->assertNotEquals($importedEntity->getExecutionOrder(), $this->entity->getExecutionOrder());
        $this->assertNotEquals($importedEntity->getActionsConfiguration(), $this->entity->getActionsConfiguration());
        $this->assertNotEquals($importedEntity->getExcludeDefinitions(), $this->entity->getExcludeDefinitions());
        $this->assertTrue($this->entity->isEnabled());

        $this->entity->import($importedEntity);

        $this->assertEquals($importedEntity->getName(), $this->entity->getName());
        $this->assertEquals($importedEntity->getLabel(), $this->entity->getLabel());
        $this->assertEquals($importedEntity->getRelatedEntity(), $this->entity->getRelatedEntity());
        $this->assertEquals($importedEntity->getExecutionOrder(), $this->entity->getExecutionOrder());
        $this->assertEquals($importedEntity->getActionsConfiguration(), $this->entity->getActionsConfiguration());
        $this->assertEquals($importedEntity->getExcludeDefinitions(), $this->entity->getExcludeDefinitions());
        $this->assertTrue($this->entity->isEnabled()); // enabled must not be changed
    }

    public function testPrePersist()
    {
        $this->assertNull($this->entity->getCreatedAt());
        $this->assertNull($this->entity->getUpdatedAt());

        $this->entity->prePersist();

        $this->assertInstanceOf('\DateTime', $this->entity->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $this->entity->getUpdatedAt());
        $this->assertEquals('UTC', $this->entity->getCreatedAt()->getTimezone()->getName());
        $this->assertEquals('UTC', $this->entity->getUpdatedAt()->getTimezone()->getName());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->entity->getUpdatedAt());

        $this->entity->preUpdate();

        $this->assertInstanceOf('\DateTime', $this->entity->getUpdatedAt());
        $this->assertEquals('UTC', $this->entity->getUpdatedAt()->getTimezone()->getName());
    }
}
