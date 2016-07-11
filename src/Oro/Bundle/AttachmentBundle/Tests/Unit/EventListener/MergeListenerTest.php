<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\EventListener\MergeListener;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class MergeListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var string $fieldPrefix */
    private $fieldPrefix;

    public function setUp()
    {
        $this->fieldPrefix = str_replace('\\', '_', Attachment::class) . '_';
    }

    public function testShouldSetTemplateOnAttachmentAssociations()
    {
        $fieldName = $this->fieldPrefix . 'field1';
        $listener = $this->getListener(['Foo\\Bar' => 'field1']);
        $event = $this->getEvent('Foo\\Bar');
        $entityMetadata = $event->getEntityMetadata();

        $listener->onBuildMetadata($event);

        $fieldsMetadata = $entityMetadata->getFieldsMetadata();
        $this->assertArrayHasKey($fieldName, $fieldsMetadata);
        $this->assertEquals(MergeListener::TEMPLATE_NAME, $fieldsMetadata[$fieldName]->get('template'));
    }

    public function testShouldNotSetTemplateOnOtherAssociations()
    {
        $listener = $this->getListener(['Foo\\Bar' => 'field1']);
        $event = $this->getEvent('Foo\\Foo');
        $entityMetadata = $event->getEntityMetadata();

        $listener->onBuildMetadata($event);

        $fieldsMetadata = $entityMetadata->getFieldsMetadata();
        $this->assertCount(0, $fieldsMetadata);
    }

    public function testShouldNotOverwriteFieldMetadata()
    {
        $fieldName = $this->fieldPrefix . 'field1';
        $listener = $this->getListener(['Foo\\Bar' => 'field1']);
        $event = $this->getEvent('Foo\\Bar');
        $entityMetadata = $event->getEntityMetadata();
        $fieldMetadata = $this->addFieldMetadata($entityMetadata, $fieldName);

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
        $this->addFieldMetadata($entityMetadata, $fieldName, 'abc.html.twig');

        $listener->onBuildMetadata($event);

        $fieldsMetadata = $entityMetadata->getFieldsMetadata();
        $this->assertEquals('abc.html.twig', $fieldsMetadata[$fieldName]->get('template'));
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
     * @param  string         $fieldName
     * @param  string         $template
     * @return FieldMetadata
     */
    private function addFieldMetadata(EntityMetadata $entityMetadata, $fieldName, $template = '')
    {
        $fieldMetadata = new FieldMetadata(['field_name' => $fieldName]);
        $fieldMetadata->set('template', $template);
        $entityMetadata->addFieldMetadata($fieldMetadata);

        return $fieldMetadata;
    }
}
