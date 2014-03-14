<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowDefinitionGridEntityNameProvider;

class WorkflowDefinitionGridEntityNameProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var WorkflowDefinitionGridEntityNameProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface')
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();

        $this->provider = new WorkflowDefinitionGridEntityNameProvider(
            $this->configProvider,
            $this->em,
            $this->translator
        );
    }

    public function testGetRelatedEntitiesChoiceConfigurable()
    {
        $entity = '\stdClass';
        $label = 'Test';

        $result = array(array('relatedEntity' => $entity));

        $qb = $this->assertResultCall($result);
        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->will($this->returnValue(true));

        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface')
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('label')
            ->will($this->returnValue('untranslated.label'));
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($entity)
            ->will($this->returnValue($config));
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('untranslated.label')
            ->will($this->returnValue($label));

        $expected = array($entity => $label);
        $this->assertEquals($expected, $this->provider->getRelatedEntitiesChoice());
    }

    public function testGetRelatedEntitiesChoiceNotConfigurable()
    {
        $entity = '\stdClass';

        $result = array(array('relatedEntity' => $entity));

        $qb = $this->assertResultCall($result);
        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->will($this->returnValue(false));

        $this->configProvider->expects($this->never())
            ->method('getConfig');
        $this->translator->expects($this->never())
            ->method('trans');

        $expected = array($entity => $entity);
        $this->assertEquals($expected, $this->provider->getRelatedEntitiesChoice());
    }

    protected function assertResultCall($result)
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getArrayResult'))
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($result));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('select')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('from')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('distinct')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        return $qb;
    }
}
