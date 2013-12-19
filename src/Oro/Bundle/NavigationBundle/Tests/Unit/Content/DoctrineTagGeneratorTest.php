<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Content;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\NavigationBundle\Content\DoctrineTagGenerator;
use Oro\Bundle\NavigationBundle\Tests\Unit\Content\Stub\EntityStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\Content\Stub\NewEntityStub;

class DoctrineTagGeneratorTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_NAME = 'Oro\\Bundle\\NavigationBundle\\Tests\\Unit\\Content\\Stub\\EntityStub';

    /** @var  DoctrineTagGenerator */
    protected $generator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject|UnitOfWork */
    protected $uow;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver */
    protected $resolver;

    public function setUp()
    {
        $this->em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->uow      = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()->getMock();
        $this->resolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()->getMock();

        $this->em->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));
        $entityClass = self::TEST_ENTITY_NAME;
        $this->resolver->expects($this->any())->method('isEntity')
            ->will(
                $this->returnCallback(
                    function ($class) use ($entityClass) {
                        return $class === $entityClass;
                    }
                )
            );

        $this->generator = new DoctrineTagGenerator($this->em, $this->resolver);
    }

    public function tearDown()
    {
        unset($this->em, $this->resolver, $this->generator);
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param mixed $data
     * @param bool  $expectedResult
     */
    public function testSupports($data, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->generator->supports($data));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'real entity object given'           => [new EntityStub(), true],
            'real entity class name given'       => [self::TEST_ENTITY_NAME, true],
            'form instance with real data given' => [$this->getFormMock(new EntityStub()), true],
            'array given'                        => [['someKey' => 'test'], false],
            'some string given'                  => ['testString', false],
            'form with array given'              => [$this->getFormMock(['someKey' => 'test']), false],
        ];
    }

    /**
     * @dataProvider generateDataProvider
     *
     * @param mixed $data
     * @param bool  $includeCollectionTag
     * @param int   $expectedCount
     */
    public function testGenerate($data, $includeCollectionTag, $expectedCount)
    {
        $isManaged = !is_string($data) && get_class($data) == self::TEST_ENTITY_NAME;
        // only once if it's object
        $this->uow->expects($this->exactly(is_object($data) ? 1 : 0))->method('getEntityState')
            ->will(
                $this->returnCallback(
                    function ($object) use ($isManaged) {
                        return $isManaged ? UnitOfWork::STATE_MANAGED : UnitOfWork::STATE_NEW;
                    }
                )
            );
        $this->uow->expects($this->exactly((int)$isManaged))->method('getEntityIdentifier')
            ->will($this->returnValue(['someIdentifierValue']));

        $result = $this->generator->generate($data, $includeCollectionTag);
        $this->assertCount($expectedCount, $result);

        $this->assertCount(
            $expectedCount,
            $this->generator->generate($data, $includeCollectionTag),
            'Should not provoke expectation error, cause tags info should be cached'
        );
    }

    /**
     * @return array
     */
    public function generateDataProvider()
    {
        return [
            'Should not generate any tags for new entity'                       => [new NewEntityStub(), false, 0],
            'Should not generate any tags for new entity even collection asked' => [new NewEntityStub(), true, 0],
            'Should generate one tag for managed entity'                        => [new EntityStub(), false, 1],
            'Should generate two tag for managed entity when collection asked'  => [new EntityStub(), true, 2],
            'Should not generate tag when data taken from string'               => [self::TEST_ENTITY_NAME, false, 0],
            'Should generate collection tag when data taken from string'        => [self::TEST_ENTITY_NAME, true, 1]
        ];
    }

    /**
     * @param mixed $data
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormMock($data)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();
        $form->expects($this->once())->method('getData')
            ->will($this->returnValue($data));

        return $form;
    }
}
