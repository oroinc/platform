<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Request\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Request\ParamConverter\DoctrineParamConverter;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ManagerRegistry;

class DoctrineParamConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var DoctrineParamConverter
     */
    protected $converter;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    protected $entityClassResolver;

    public function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ManagerRegistry')) {
            $this->markTestSkipped();
        }
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();


        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry  = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->converter = new DoctrineParamConverter(
            $this->registry,
            $this->securityFacade,
            $this->entityClassResolver
        );
    }

    public function createConfiguration($class = null, array $options = null, $name = 'arg', $isOptional = false)
    {
        $methods = array('getClass', 'getAliasName', 'getOptions', 'getName', 'allowArray');
        if (null !== $isOptional) {
            $methods[] = 'isOptional';
        }
        $config = $this
            ->getMockBuilder('Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter')
            ->setMethods($methods)
            ->disableOriginalConstructor()
            ->getMock();
        if ($options !== null) {
            $config->expects($this->once())
                ->method('getOptions')
                ->will($this->returnValue($options));
        }
        if ($class !== null) {
            $config->expects($this->any())
                ->method('getClass')
                ->will($this->returnValue($class));
        }
        $config->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        if (null !== $isOptional) {
            $config->expects($this->any())
                ->method('isOptional')
                ->will($this->returnValue($isOptional));
        }

        return $config;
    }

    /**
     * @dataProvider idsProvider
     */
    public function testApply($object, $isGranted, $class, $isCorrectClass)
    {
        $annotation = new Acl(
            [
                'id'         => 1,
                'type'       => 'entity',
                'class'      => $class,
                'permission' => 'EDIT'
            ]
        );
        $this->securityFacade->expects($this->any())
            ->method('getClassMethodAnnotation')
            ->will($this->returnValue($annotation));

        $this->entityClassResolver->expects($this->any())
            ->method('isEntity')
            ->will($this->returnValue(true));

        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will($this->returnValue($class));

        $request = new Request();
        $request->attributes->set('id', 1);
        $request->attributes->set('_controller', 'Oro\Test::test');

        $config = $this->createConfiguration(get_class($object), array('id' => 'id'), 'arg');

        $manager          = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $objectRepository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->will($this->returnValue($manager));

        $manager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($objectRepository));

        $objectRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($object));

        if ($isCorrectClass) {
            $this->securityFacade->expects($this->once())
                ->method('isGranted')
                ->will($this->returnValue($isGranted));

            if (!$isGranted) {
                $this->setExpectedException(
                    'Symfony\Component\Security\Core\Exception\AccessDeniedException',
                    'You do not get EDIT permission for this object'
                );
            }
        }

        $this->converter->apply($request, $config);

        $this->assertTrue($request->attributes->has('_oro_access_checked'));

        if (!$isGranted || !$isCorrectClass) {
            $this->assertFalse($request->attributes->get('_oro_access_checked'));
        } else {
            $this->assertTrue($request->attributes->get('_oro_access_checked'));
        }

    }

    public function idsProvider()
    {
        return [
            [new CmsAddress(), true, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress', true],
            [new CmsAddress(), false, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\CmsAddress', true],
            [new CmsAddress(), true, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\wrongClass', false],
            [new CmsAddress(), false, 'Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Models\CMS\wrongClass', false],
        ];
    }
}
