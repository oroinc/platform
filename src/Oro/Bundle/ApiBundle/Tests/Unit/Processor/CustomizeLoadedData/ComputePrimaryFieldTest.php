<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\ComputePrimaryField;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;

class ComputePrimaryFieldTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomizeLoadedDataContext */
    private $context;

    /** @var ComputePrimaryField */
    private $processor;

    protected function setUp()
    {
        $this->context = new CustomizeLoadedDataContext();
        $this->processor = new ComputePrimaryField(
            'enabledRole',
            'roles',
            'name',
            'enabled'
        );
    }

    public function testProcessWhenNoData()
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWhenNoConfigForPrimaryField()
    {
        $config = new EntityDefinitionConfig();

        $this->context->setResult(
            [
                'roles' => [
                    ['name' => 'role1', 'enabled' => false],
                    ['name' => 'role2', 'enabled' => true]
                ]
            ]
        );
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'roles' => [
                    ['name' => 'role1', 'enabled' => false],
                    ['name' => 'role2', 'enabled' => true]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForExcludedPrimaryField()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole')->setExcluded();
        $rolesConfig = $config->addField('roles')->getOrCreateTargetEntity();
        $rolesConfig->addField('name');
        $rolesConfig->addField('enabled');

        $this->context->setResult(
            [
                'roles' => [
                    ['name' => 'role1', 'enabled' => false],
                    ['name' => 'role2', 'enabled' => true]
                ]
            ]
        );
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'roles' => [
                    ['name' => 'role1', 'enabled' => false],
                    ['name' => 'role2', 'enabled' => true]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenPrimaryFieldIsAlreadySet()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesConfig = $config->addField('roles')->getOrCreateTargetEntity();
        $rolesConfig->addField('name');
        $rolesConfig->addField('enabled');

        $this->context->setResult(
            [
                'enabledRole' => 'role1',
                'roles'       => [
                    ['name' => 'role1', 'enabled' => false],
                    ['name' => 'role2', 'enabled' => true]
                ]
            ]
        );
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'enabledRole' => 'role1',
                'roles'       => [
                    ['name' => 'role1', 'enabled' => false],
                    ['name' => 'role2', 'enabled' => true]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenPrimaryFieldIsNotSetYet()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('enabledRole');
        $rolesConfig = $config->addField('roles')->getOrCreateTargetEntity();
        $rolesConfig->addField('name');
        $rolesConfig->addField('enabled');

        $this->context->setResult(
            [
                'roles' => [
                    ['name' => 'role1', 'enabled' => false],
                    ['name' => 'role2', 'enabled' => true]
                ]
            ]
        );
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'enabledRole' => 'role2',
                'roles'       => [
                    ['name' => 'role1', 'enabled' => false],
                    ['name' => 'role2', 'enabled' => true]
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForRenamedFields()
    {
        $this->processor = new ComputePrimaryField(
            'enabledRole',
            'roles',
            'name',
            'enabled'
        );

        $config = new EntityDefinitionConfig();
        $config->addField('renamedEnabledRole')->setPropertyPath('enabledRole');
        $rolesField = $config->addField('renamedRoles');
        $rolesField->setPropertyPath('roles');
        $rolesConfig = $rolesField->getOrCreateTargetEntity();
        $rolesConfig->addField('renamedName')->setPropertyPath('name');
        $rolesConfig->addField('renamedEnabled')->setPropertyPath('enabled');

        $this->context->setResult(
            [
                'renamedRoles' => [
                    ['renamedName' => 'role1', 'renamedEnabled' => false],
                    ['renamedName' => 'role2', 'renamedEnabled' => true]
                ]
            ]
        );
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        self::assertEquals(
            [
                'renamedEnabledRole' => 'role2',
                'renamedRoles'       => [
                    ['renamedName' => 'role1', 'renamedEnabled' => false],
                    ['renamedName' => 'role2', 'renamedEnabled' => true]
                ]
            ],
            $this->context->getResult()
        );
    }
}
