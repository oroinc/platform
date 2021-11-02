<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\EventListener\MergeListener;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

class MergeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    private $fieldPrefix;

    protected function setUp(): void
    {
        $this->fieldPrefix = str_replace('\\', '_', Attachment::class) . '_';
    }

    public function testShouldSetMetadataToAttachmentAssociations()
    {
        $fieldName = $this->fieldPrefix . 'field1';
        $listener = $this->getListener(['Foo\\Bar' => 'field1']);
        $event = $this->getEvent('Foo\\Bar');
        $entityMetadata = $event->getEntityMetadata();

        $listener->onBuildMetadata($event);

        $fieldsMetadata = $entityMetadata->getFieldsMetadata();
        $this->assertArrayHasKey($fieldName, $fieldsMetadata);
        $this->assertEquals(
            [
                'template' => MergeListener::TEMPLATE_NAME,
                'merge_modes' => [MergeModes::UNITE, MergeModes::REPLACE],
                'field_name' => $fieldName,
            ],
            $fieldsMetadata[$fieldName]->all()
        );
    }

    public function testShouldNotSetMetadataToUnknownAssociations()
    {
        $listener = $this->getListener(['Foo\\Bar' => 'field1']);
        $event = $this->getEvent('Foo\\Foo');
        $entityMetadata = $event->getEntityMetadata();

        $listener->onBuildMetadata($event);

        $fieldsMetadata = $entityMetadata->getFieldsMetadata();
        $this->assertCount(0, $fieldsMetadata);
    }

    public function testShouldNotOverwriteFieldMetadataReference()
    {
        $fieldName = $this->fieldPrefix . 'field1';
        $listener = $this->getListener(['Foo\\Bar' => 'field1']);
        $event = $this->getEvent('Foo\\Bar');
        $entityMetadata = $event->getEntityMetadata();
        $fieldMetadata = $this->addFieldMetadata($entityMetadata, ['field_name' => $fieldName]);

        $listener->onBuildMetadata($event);

        $fieldsMetadata = $entityMetadata->getFieldsMetadata();
        $this->assertCount(1, $fieldsMetadata);
        $this->assertArrayHasKey($fieldName, $fieldsMetadata);
        $this->assertSame($fieldMetadata, $fieldsMetadata[$fieldName]);
    }

    public function testShouldNotOverwriteTemplate()
    {
        $fieldName = $this->fieldPrefix . 'field1';
        $listener = $this->getListener(['Foo\\Bar' => 'field1']);
        $event = $this->getEvent('Foo\\Bar');
        $entityMetadata = $event->getEntityMetadata();
        $fieldOptions = ['template' => 'abc.html.twig', 'field_name' => $fieldName];
        $this->addFieldMetadata($entityMetadata, $fieldOptions);

        $listener->onBuildMetadata($event);

        $fieldsMetadata = $entityMetadata->getFieldsMetadata();
        $this->assertEquals('abc.html.twig', $fieldsMetadata[$fieldName]->get('template'));
    }

    public function testShouldCombineMergeModes()
    {
        $fieldName = $this->fieldPrefix . 'field1';
        $listener = $this->getListener(['Foo\\Bar' => 'field1']);
        $event = $this->getEvent('Foo\\Bar');
        $entityMetadata = $event->getEntityMetadata();
        $fieldOptions = ['merge_modes' => 'test_mode', 'field_name' => $fieldName];
        $this->addFieldMetadata($entityMetadata, $fieldOptions);

        $listener->onBuildMetadata($event);

        $fieldsMetadata = $entityMetadata->getFieldsMetadata();
        $this->assertEquals(
            [MergeModes::UNITE, MergeModes::REPLACE, 'test_mode'],
            $fieldsMetadata[$fieldName]->get('merge_modes')
        );
    }

    private function getListener(array $targets = []): MergeListener
    {
        $manager = $this->createMock(AttachmentManager::class);
        $manager->expects($this->any())
            ->method('getAttachmentTargets')
            ->willReturn($targets);

        return new MergeListener($manager);
    }

    private function getEvent(string $className): EntityMetadataEvent
    {
        $doctrineMetadata = new DoctrineMetadata(['name' => $className]);
        $entityMetadata = new EntityMetadata([], $doctrineMetadata);

        return new EntityMetadataEvent($entityMetadata);
    }

    private function addFieldMetadata(EntityMetadata $entityMetadata, array $options): FieldMetadata
    {
        $fieldMetadata = new FieldMetadata($options);
        $entityMetadata->addFieldMetadata($fieldMetadata);

        return $fieldMetadata;
    }
}
