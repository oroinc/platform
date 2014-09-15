<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Request\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Request\ParamConverter\DoctrineParamConverter;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ManagerRegistry;

class DoctrineParamConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var DoctrineParamConverter
     */
    protected $converter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ManagerRegistry')) {
            $this->markTestSkipped();
        }
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry  = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->converter = new DoctrineParamConverter(
            $this->registry,
            $this->securityFacade
        );
    }

    /**
     * @dataProvider idsProvider
     */
    public function testApply($object, $isGranted, $class, $isCorrectClass)
    {
        $manager          = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $objectRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
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
        $this->securityFacade->expects($this->any())
            ->method('isRequestObjectIsGranted')
            ->will($this->returnValue($isGranted));

        if ($isGranted === -1) {
            $this->setExpectedException(
                'Symfony\Component\Security\Core\Exception\AccessDeniedException',
                'You do not get EDIT permission for this object'
            );
            $this->securityFacade->expects($this->any())
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
}
