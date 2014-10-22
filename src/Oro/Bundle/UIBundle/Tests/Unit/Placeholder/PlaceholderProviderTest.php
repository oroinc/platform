<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Placeholder;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderProvider;

class PlaceholderProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var PlaceholderProvider
     */
    protected $provider;

    protected $placeholders = [
        'placeholders' => [
            'test_placeholder' => [
                'items' => ['item1', 'item2', 'item3', 'item4', 'item5', 'item6', 'item7', 'item8', 'item9']
            ]
        ],
        'items'        => [
            'item1' => [
                'template' => 'template1'
            ],
            'item2' => [
                'template'   => 'template2',
                'applicable' => '@service->isApplicable($entity$)',
                'data'       => '@service->getData($entity$)'
            ],
            'item3' => [
                'template'   => 'template3',
                'applicable' => '@service->isApplicable($entity$)',
                'data'       => '@service->getData($entity$)'
            ],
            'item4' => [
                'template'   => 'template4',
                'applicable' => true
            ],
            'item5' => [
                'template'   => 'template5',
                'applicable' => false
            ],
            'item6' => [
                'template' => 'template6',
                'acl'      => 'acl_ancestor'
            ],
            'item7' => [
                'template' => 'template7',
                'acl'      => 'acl_ancestor'
            ],
            'item8' => [
                'template' => 'template8',
                'acl'      => ['acl_ancestor1', 'acl_ancestor2']
            ],
            'item9' => [
                'template' => 'template9',
                'acl'      => ['acl_ancestor1', 'acl_ancestor2']
            ],
        ]
    ];

    protected function setUp()
    {
        $this->resolver       = $this->getMock('Oro\Component\Config\Resolver\ResolverInterface');
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PlaceholderProvider($this->placeholders, $this->resolver, $this->securityFacade);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetPlaceholderItems()
    {
        $placeholderName = 'test_placeholder';

        $variables = ['foo' => 'bar'];

        $items = $this->placeholders['items'];

        $index          = 0;
        $isGrantedIndex = 0;

        // item1
        $this->resolver->expects($this->at($index++))
            ->method('resolve')
            ->with($items['item1'], $variables)
            ->will($this->returnValue($items['item1']));

        // item2
        $this->resolver->expects($this->at($index++))
            ->method('resolve')
            ->with(['applicable' => $items['item2']['applicable']], $variables)
            ->will($this->returnValue(['applicable' => true]));
        unset($items['item2']['applicable']);
        $this->resolver->expects($this->at($index++))
            ->method('resolve')
            ->with($items['item2'], $variables)
            ->will($this->returnValue($items['item2']));

        // item3
        $this->resolver->expects($this->at($index++))
            ->method('resolve')
            ->with(['applicable' => $items['item3']['applicable']], $variables)
            ->will($this->returnValue(['applicable' => false]));

        // item4
        $this->resolver->expects($this->at($index++))
            ->method('resolve')
            ->with(['applicable' => $items['item4']['applicable']], $variables)
            ->will($this->returnValue(['applicable' => $items['item4']['applicable']]));
        unset($items['item4']['applicable']);
        $this->resolver->expects($this->at($index++))
            ->method('resolve')
            ->with($items['item4'], $variables)
            ->will($this->returnValue($items['item4']));

        // item5
        $this->resolver->expects($this->at($index++))
            ->method('resolve')
            ->with(['applicable' => $items['item5']['applicable']], $variables)
            ->will($this->returnValue(['applicable' => $items['item5']['applicable']]));

        // item6
        $this->securityFacade->expects($this->at($isGrantedIndex++))
            ->method('isGranted')
            ->with('acl_ancestor')
            ->will($this->returnValue(false));

        // item7
        $this->securityFacade->expects($this->at($isGrantedIndex++))
            ->method('isGranted')
            ->with('acl_ancestor')
            ->will($this->returnValue(true));
        unset($items['item7']['acl']);
        $this->resolver->expects($this->at($index++))
            ->method('resolve')
            ->with($items['item7'], $variables)
            ->will($this->returnValue($items['item7']));

        // item8
        $this->securityFacade->expects($this->at($isGrantedIndex++))
            ->method('isGranted')
            ->with('acl_ancestor1')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->at($isGrantedIndex++))
            ->method('isGranted')
            ->with('acl_ancestor2')
            ->will($this->returnValue(true));
        unset($items['item8']['acl']);
        $this->resolver->expects($this->at($index++))
            ->method('resolve')
            ->with($items['item8'], $variables)
            ->will($this->returnValue($items['item8']));

        // item9
        $this->securityFacade->expects($this->at($isGrantedIndex++))
            ->method('isGranted')
            ->with('acl_ancestor1')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->at($isGrantedIndex++))
            ->method('isGranted')
            ->with('acl_ancestor2')
            ->will($this->returnValue(false));

        $expected = [
            $items['item1'],
            $items['item2'],
            $items['item4'],
            $items['item7'],
            $items['item8']
        ];

        $this->assertEquals(
            $expected,
            $this->provider->getPlaceholderItems($placeholderName, $variables)
        );
    }
}
