<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Form\FormScopeCriteriaResolver;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

class FormScopeCriteriaResolverTest extends TestCase
{
    private ScopeManager&MockObject $manager;
    private FormScopeCriteriaResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager = $this->createMock(ScopeManager::class);

        $this->resolver = new FormScopeCriteriaResolver($this->manager);
    }

    public function testResolve(): void
    {
        // parent form contains scope options which should be used in result context
        $parentForm = $this->createMock(FormInterface::class);
        $parentFormConfig = $this->createMock(FormConfigInterface::class);
        $rootScope = new Scope();
        $this->manager->expects(self::any())
            ->method('getCriteriaByScope')
            ->with($rootScope, 'test_scope')
            ->willReturn(
                new ScopeCriteria(
                    [
                        'field1' => 'parent_scope_value',
                        'field2' => 'parent_scope_value',
                    ],
                    $this->createMock(ClassMetadataFactory::class)
                )
            );
        $parentFormConfig->expects(self::any())
            ->method('hasOption')
            ->willReturnMap([
                ['context', false],
                ['scope', true],
            ]);
        $parentFormConfig->expects(self::any())
            ->method('getOption')
            ->with('scope')
            ->willReturn($rootScope);
        $parentForm->expects(self::any())
            ->method('getConfig')
            ->willReturn($parentFormConfig);

        // form with context in options, form options has greater priority then parent form's
        $form = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects(self::any())
            ->method('hasOption')
            ->with('context')
            ->willReturn(true);
        $formConfig->expects(self::any())
            ->method('getOption')
            ->with('context')
            ->willReturn(['field1' => 'context_value']);
        $form->expects(self::any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $form->expects(self::any())
            ->method('getParent')
            ->willReturn($parentForm);

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
