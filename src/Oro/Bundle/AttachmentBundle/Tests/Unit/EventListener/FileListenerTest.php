<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\EventListener\FileListener;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;

class FileListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileListener  */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    protected $manager;

    /**
     * @var File
     */
    protected $attachment;

    public function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new FileListener($this->manager);
    }

    /**
     * @dataProvider getData
     */
    public function testPrePersist(LifecycleEventArgs $args, $expectsMethod)
    {
        $entity = $args->getEntity();
        $this->setExpects($entity, ['preUpload'], $expectsMethod);
        $this->listener->prePersist($args);
    }

    /**
     * @dataProvider getData
     */
    public function testPreUpdate(LifecycleEventArgs $args, $expectsMethod)
    {
        $entity = $args->getEntity();
        $this->setExpects($entity, ['preUpload'], $expectsMethod);
        $this->listener->preUpdate($args);
    }

    /**
     * @dataProvider getData
     */
    public function testPostPersist(LifecycleEventArgs $args, $expectsMethod)
    {
        $entity = $args->getEntity();
        $this->setExpects($entity, ['upload', 'checkOnDelete'], $expectsMethod);
        $this->listener->postPersist($args);
    }

    /**
     * @dataProvider getData
     */
    public function testPostUpdate(LifecycleEventArgs $args, $expectsMethod)
    {
        $entity = $args->getEntity();
        $this->setExpects($entity, ['upload', 'checkOnDelete'], $expectsMethod);
        $this->listener->postUpdate($args);
    }

    protected function setExpects($entity, $methodNames, $expectsMethod)
    {
        if ($expectsMethod) {
            foreach ($methodNames as $methodName) {
                $this->manager->expects($this->once())
                    ->method($methodName)
                    ->with($entity);
            }

        } else {
            foreach ($methodNames as $methodName) {
                $this->manager->expects($this->never())
                    ->method($methodName);
            }
        }
    }

    public function getData()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $correctArguments = new LifecycleEventArgs(new File(), $em);
        $incorrectArguments = new LifecycleEventArgs(new TestClass(), $em);

        return [
            'correctData' => [$correctArguments, true],
            'incorrectData' => [$incorrectArguments, false]
        ];
    }
}
