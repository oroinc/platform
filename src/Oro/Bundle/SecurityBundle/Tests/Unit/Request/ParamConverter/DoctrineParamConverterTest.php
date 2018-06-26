<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Request\ParamConverter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Request\ParamConverter\DoctrineParamConverter;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

class DoctrineParamConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var DoctrineParamConverter */
    protected $converter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $requestAuthorizationChecker;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->requestAuthorizationChecker = $this->createMock(RequestAuthorizationChecker::class);

        $this->converter = new DoctrineParamConverter(
            $this->registry,
            $this->requestAuthorizationChecker
        );
    }

    /**
     * @dataProvider idsProvider
     */
    public function testApply($object, $isGranted, $class, $isCorrectClass)
    {
        $manager          = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $objectRepository = $this->createMock('Doctrine\Common\Persistence\ObjectRepository');
        $manager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($objectRepository));
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->will($this->returnValue($manager));
        $objectRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($object));

        $request = new Request();
        $request->attributes->set('_oro_access_checked', false);
        $request->attributes->set('id', 1);
        $config = new ParamConverter(
            [
                'class' => get_class($object),
                'name' => 'arg',
                'options' => ['id' => 'id']
            ]
        );
        $annotation = new Acl(
            [
                'id'         => 1,
                'type'       => 'entity',
                'class'      => $class,
                'permission' => 'EDIT'
            ]
        );
        $this->requestAuthorizationChecker->expects($this->any())
            ->method('isRequestObjectIsGranted')
            ->will($this->returnValue($isGranted));

        if ($isGranted === -1) {
            $this->expectException('Symfony\Component\Security\Core\Exception\AccessDeniedException');
            $this->expectExceptionMessage('You do not get EDIT permission for this object');
            $this->requestAuthorizationChecker->expects($this->any())
                ->method('getRequestAcl')
                ->will($this->returnValue($annotation));
        }

        $this->converter->apply($request, $config);

        $this->assertTrue($request->attributes->has('_oro_access_checked'));

        if ($isGranted === -1 || !$isCorrectClass) {
            $this->assertFalse($request->attributes->get('_oro_access_checked'));
        }
        if ($isGranted === 0) {
            $this->assertTrue($request->attributes->get('_oro_access_checked'));
        }
    }

    public function idsProvider()
    {
        return [
            [new CmsAddress(), 1, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress', true],
            [new CmsAddress(), -1, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress', true],
            [new CmsAddress(), -1, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\wrongClass', false],
            [new CmsAddress(), -1, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\wrongClass', false],
        ];
    }

    public function testSupportsWithoutClass()
    {
        $config = new ParamConverter([]);

        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->registry->expects($this->never())
            ->method('getManager');

        $this->assertFalse($this->converter->supports($config));
    }

    public function testSupportsWithoutConfiguredEntityManager()
    {
        $config = new ParamConverter(['class' => 'stdClass']);

        $objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('stdClass')
            ->willReturn($objectManager);

        $this->assertTrue($this->converter->supports($config));
    }

    public function testSupportsWithoutConfiguredEntityManagerAndNotManageableClass()
    {
        $config = new ParamConverter(['class' => 'stdClass']);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('stdClass')
            ->willReturn(null);

        $this->assertFalse($this->converter->supports($config));
    }

    public function testSupportsWithConfiguredEntityManager()
    {
        $config = new ParamConverter(['class' => 'stdClass']);
        $config->setOptions(['entity_manager' => 'foo']);

        $metadataFactory = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('isTransient')
            ->with('stdClass')
            ->willReturn(false);

        $objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->registry->expects($this->once())
            ->method('getManager')
            ->with('foo')
            ->willReturn($objectManager);

        $this->assertTrue($this->converter->supports($config));
    }

    public function testSupportsWithConfiguredEntityManagerAndTransientClass()
    {
        $config = new ParamConverter(['class' => 'stdClass']);
        $config->setOptions(['entity_manager' => 'foo']);

        $metadataFactory = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('isTransient')
            ->with('stdClass')
            ->willReturn(true);

        $objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $objectManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->registry->expects($this->once())
            ->method('getManager')
            ->with('foo')
            ->willReturn($objectManager);

        $this->assertFalse($this->converter->supports($config));
    }
}
