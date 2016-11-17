<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Form\FormScopeCriteriaResolver;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

class FormScopeCriteriaResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var FormScopeCriteriaResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resolver;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolver = new FormScopeCriteriaResolver($this->manager);
    }

    public function testResolve()
    {
        // parent form contains scope options which should be used in result context
        $parentForm = $this->getMock(FormInterface::class);
        $parentFormConfig = $this->getMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $this->manager->method('getCriteriaByScope')
            ->with($rootScope, 'test_scope')
            ->willReturn(
                new ScopeCriteria(
                    [
                        'field1' => 'parent_scope_value',
                        'field2' => 'parent_scope_value',
                    ]
                )
            );
        $parentFormConfig->method('hasOption')
            ->willReturnMap(
                [
                    ['context', false],
                    ['scope', true],
                ]
            );
        $parentFormConfig->method('getOption')->with('scope')->willReturn($rootScope);
        $parentForm->method('getConfig')->willReturn($parentFormConfig);

        // form with context in options, form options has greater priority then parent form's
        $form = $this->getMock(FormInterface::class);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $formConfig->method('hasOption')->with('context')->willReturn(true);
        $formConfig->method('getOption')->with('context')->willReturn(['field1' => 'context_value']);
        $form->method('getConfig')->willReturn($formConfig);
        $form->method('getParent')->willReturn($parentForm);

        // assert criteria created with proper context
        $this->manager->expects($this->once())
            ->method('getCriteria')
            ->with(
                'test_scope',
                [
                    'field1' => 'context_value',
                    'field2' => 'parent_scope_value',
                ]
            );
        $this->resolver->resolve($form, 'test_scope');
    }
}
