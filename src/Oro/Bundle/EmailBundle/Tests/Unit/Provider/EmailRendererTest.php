<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEntityForVariableProvider;

class EmailRendererTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $loader;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $variablesProvider;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityPolicy;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $sandbox;

    /** @var string */
    protected $cacheKey = 'test.key';

    /** @var EmailRenderer */
    protected $renderer = 'test.key';

    /** @var TranslatorInterface */
    protected $translation;

    /**
     * setup mocks
     */
    protected function setUp()
    {
        $this->loader = $this->getMock('\Twig_Loader_String');

        $this->securityPolicy = $this->getMockBuilder('\Twig_Sandbox_SecurityPolicy')
            ->disableOriginalConstructor()->getMock();

        $this->sandbox = $this->getMockBuilder('\Twig_Extension_Sandbox')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sandbox->expects($this->once())->method('getName')
            ->will($this->returnValue('sandbox'));
        $this->sandbox->expects($this->once())->method('getSecurityPolicy')
            ->will($this->returnValue($this->securityPolicy));

        $this->variablesProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\VariablesProvider')
            ->disableOriginalConstructor()->getMock();

        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\Cache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translation = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();
        $this->translation->expects($this->any())->method('trans')
            ->will($this->returnArgument(0));
        ;
    }

    /**
     * test configureSandbox method
     */
    public function testConfigureSandboxCached()
    {
        $entityClass = 'TestEntity';

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with($this->cacheKey)
            ->will(
                $this->returnValue(
                    serialize(
                        [
                            'properties' => [
                                $entityClass => ['field2']
                            ],
                            'methods'    => [
                                $entityClass => ['getField1']
                            ]
                        ]
                    )
                )
            );

        $this->getRendererInstance();
    }

    /**
     * configure Sanbox method with not cached scenario
     */
    public function testConfigureSandboxNotCached()
    {
        $entityClass = 'TestEntity';

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with($this->cacheKey)
            ->will($this->returnValue(false));

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->cacheKey,
                serialize(
                    [
                        'formatters' => [
                            $entityClass => []
                        ],
                        'properties' => [
                            $entityClass => ['field2']
                        ],
                        'methods'    => [
                            $entityClass => ['getField1']
                        ],
                        'default_formatter' => [
                            $entityClass => []
                        ]
                    ]
                )
            );

        $this->variablesProvider->expects($this->once())
            ->method('getEntityVariableGetters')
            ->with(null)
            ->will(
                $this->returnValue(
                    [$entityClass => ['field1' => 'getField1', 'field2' => null]]
                )
            );

        $this->getRendererInstance();
    }

    /**
     * Compile message test
     */
    public function testCompileMessage()
    {
        $entity = new TestEntityForVariableProvider();
        $entity->setField1('Test');
        $entityClass = get_class($entity);
        $systemVars  = ['testVar' => 'test_system'];

        $this->cache->expects($this->any())->method('fetch')
            ->with($this->cacheKey)
            ->will(
                $this->returnValue(
                    serialize(
                        [
                            'properties' => [],
                            'methods'    => [
                                $entityClass => ['getField1']
                            ]
                        ]
                    )
                )
            );

        $content = 'test content <a href="sdfsdf">asfsdf</a> {{ entity.field1|oro_html_sanitize }} N/A';
        $subject = 'subject';

        $emailTemplate = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');
        $emailTemplate->expects($this->once())->method('getContent')
            ->will($this->returnValue($content));
        $emailTemplate->expects($this->once())->method('getSubject')
            ->will($this->returnValue($subject));

        $this->variablesProvider->expects($this->once())->method('getSystemVariableValues')
            ->will($this->returnValue($systemVars));

        $templateParams = array(
            'entity' => $entity,
            'system' => $systemVars
        );

        $renderer = $this->getRendererInstance();

        $renderer->expects($this->at(0))
            ->method('render')
            ->with($content, $templateParams);
        $renderer->expects($this->at(1))
            ->method('render')
            ->with($subject, $templateParams);

        $result = $renderer->compileMessage($emailTemplate, $templateParams);

        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);
    }

    public function testNotExistField()
    {
        $content = 'content {{ entity.sub.crp }}, {{ entity.field1 }}, ' .
            '{{ entity.field2.field1 }}, {{ entity.field2.25453 }}, {{ system.currentDate }}';

        $entity2 = new TestEntityForVariableProvider();
        $entity2->setField1(new \DateTime('now'));

        $entity = new TestEntityForVariableProvider();
        $entity->setField1(new \DateTime('now'));
        $entity->setField2($entity2);

        $this->cache
            ->expects($this->any())
            ->method('fetch')
            ->with($this->cacheKey)
            ->will(
                $this->returnValue(
                    serialize(
                        [
                            'properties' => [],
                            'methods'    => [
                                get_class($entity) => ['getField1']
                            ]
                        ]
                    )
                )
            );

        $renderer = $this->getRendererInstance();
        $renderer->expects($this->any())->method('render')
            ->will($this->returnArgument(0));

        $result = $renderer->compileMessage(new EmailTemplate('', $content), ['entity' => $entity]);

        $this->assertEquals(
            'content oro.email.variable.not.found, {{ entity.field1|oro_html_sanitize }}, ' .
            '{{ entity.field2.field1|oro_html_sanitize }}, oro.email.variable.not.found, ' .
            '{{ system.currentDate }}',
            $renderedContent = $result[1]
        );
    }

    /**
     * Compile template preview test
     */
    public function testCompilePreview()
    {
        $entity = new TestEntityForVariableProvider();

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with($this->cacheKey)
            ->will(
                $this->returnValue(
                    serialize(
                        [
                            'properties' => [],
                            'methods'    => [
                                get_class($entity) => ['getField1']
                            ]
                        ]
                    )
                )
            );

        $content = 'test content <a href="sdfsdf">asfsdf</a> {{ entity.field1 }} {{ system.testVar }}';

        $emailTemplate = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');
        $emailTemplate->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($content));

        $templateParams = array();

        $renderer = $this->getRendererInstance();

        $renderer->expects($this->at(0))
            ->method('render')
            ->with('{% verbatim %}' . $content . '{% endverbatim %}', $templateParams);
        $renderer->compilePreview($emailTemplate);
    }

    /**
     * @return EmailRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getRendererInstance()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject */
        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject */
        $em = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine
            ->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($em));

        return $this->getMock(
            'Oro\Bundle\EmailBundle\Provider\EmailRenderer',
            array('render'),
            array(
                $this->loader,
                array(),
                $this->variablesProvider,
                $this->cache,
                $this->cacheKey,
                $this->sandbox,
                $this->translation
            )
        );
    }
}
