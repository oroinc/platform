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
    /** @var string $fieldPrefix */
    private $fieldPrefix;

    public function setUp()
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

    /**
     * @param  array  $targets
     * @return MergeListener
     */
    private function getListener(array $targets = [])
    {
        $manager = $this->getMockBuilder(AttachmentManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getAttachmentTargets')
            ->will($this->returnValue($targets));

        $listener = new MergeListener($manager);

        return $listener;
    }

    /**
     * @param  string $className
     * @return EntityMetadataEvent
     */
    private function getEvent($className)
    {
        $doctrineMetadata = new DoctrineMetadata(['name' => $className]);
        $entityMetadata = new EntityMetadata([], $doctrineMetadata);
        $event = new EntityMetadataEvent($entityMetadata);

        return $event;
    }

    /**
     * @param  EntityMetadata $entityMetadata
     * @param  array          $options
     * @return FieldMetadata
     */
    private function addFieldMetadata(EntityMetadata $entityMetadata, array $options)
    {
        $fieldMetadata = new FieldMetadata($options);
        $entityMetadata->addFieldMetadata($fieldMetadata);

        return $fieldMetadata;
    }
}
