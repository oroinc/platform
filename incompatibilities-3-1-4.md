- [InstallerBundle](#installerbundle)
- [LayoutBundle](#layoutbundle)
- [WorkflowBundle](#workflowbundle)

InstallerBundle
---------------
* The following classes were removed:
   - `NamespaceMigrationProviderPass`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/InstallerBundle/DependencyInjection/CompilerPass/NamespaceMigrationProviderPass.php#L9 "Oro\Bundle\InstallerBundle\DependencyInjection\CompilerPass\NamespaceMigrationProviderPass")</sup>
   - `ConfigUpgradeCommand`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/InstallerBundle/Command/ConfigUpgradeCommand.php#L10 "Oro\Bundle\InstallerBundle\Command\ConfigUpgradeCommand")</sup>
   - `UpgradeCommand`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/InstallerBundle/Command/UpgradeCommand.php#L9 "Oro\Bundle\InstallerBundle\Command\UpgradeCommand")</sup>
   - `NamespaceMigration`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/InstallerBundle/CacheWarmer/NamespaceMigration.php#L11 "Oro\Bundle\InstallerBundle\CacheWarmer\NamespaceMigration")</sup>
* The `OroInstallerBundle::build`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/InstallerBundle/OroInstallerBundle.php#L14 "Oro\Bundle\InstallerBundle\OroInstallerBundle::build")</sup> method was removed.

LayoutBundle
------------
* The `DebugLayoutContext`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/Command/Util/DebugLayoutContext.php#L8 "Oro\Bundle\LayoutBundle\Command\Util\DebugLayoutContext")</sup> class was removed.
* The following methods in class `DataCollectorExtension`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/Layout/Block/Extension/DataCollectorExtension.php#L32 "Oro\Bundle\LayoutBundle\Layout\Block\Extension\DataCollectorExtension")</sup> were removed:
   - `buildBlock`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/Layout/Block/Extension/DataCollectorExtension.php#L32 "Oro\Bundle\LayoutBundle\Layout\Block\Extension\DataCollectorExtension::buildBlock")</sup>
   - `buildView`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/Layout/Block/Extension/DataCollectorExtension.php#L40 "Oro\Bundle\LayoutBundle\Layout\Block\Extension\DataCollectorExtension::buildView")</sup>
* The following methods in class `ConfigurationPass`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/DependencyInjection/Compiler/ConfigurationPass.php#L59 "Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass")</sup> were removed:
   - `getBlockTypes`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/DependencyInjection/Compiler/ConfigurationPass.php#L59 "Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass::getBlockTypes")</sup>
   - `getDataProviders`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/DependencyInjection/Compiler/ConfigurationPass.php#L158 "Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass::getDataProviders")</sup>
* The following methods in class `LayoutDataCollector`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/DataCollector/LayoutDataCollector.php#L114 "Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector")</sup> were removed:
   - `collectBuildBlockOptions`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/DataCollector/LayoutDataCollector.php#L114 "Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector::collectBuildBlockOptions")</sup>
   - `collectBuildViewOptions`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/DataCollector/LayoutDataCollector.php#L132 "Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector::collectBuildViewOptions")</sup>
   - `collectBlockTree`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/DataCollector/LayoutDataCollector.php#L146 "Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector::collectBlockTree")</sup>
* The following methods in class `DebugCommand`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/Command/DebugCommand.php#L111 "Oro\Bundle\LayoutBundle\Command\DebugCommand")</sup> were removed:
   - `dumpOptionResolver`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/Command/DebugCommand.php#L111 "Oro\Bundle\LayoutBundle\Command\DebugCommand::dumpOptionResolver")</sup>
   - `formatValue`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/Command/DebugCommand.php#L141 "Oro\Bundle\LayoutBundle\Command\DebugCommand::formatValue")</sup>
* The `DebugOptionsResolverDecorator::__construct(OptionsResolver $optionsResolver)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/LayoutBundle/Command/Util/DebugOptionsResolverDecorator.php#L19 "Oro\Bundle\LayoutBundle\Command\Util\DebugOptionsResolverDecorator")</sup> method was changed to `DebugOptionsResolverDecorator::__construct(OptionsResolver $optionsResolver)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.4/src/Oro/Bundle/LayoutBundle/Command/Util/DebugOptionsResolverDecorator.php#L23 "Oro\Bundle\LayoutBundle\Command\Util\DebugOptionsResolverDecorator")</sup>

WorkflowBundle
--------------
* The following classes were removed:
   - `DefinitionUpgrade20Command`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/DefinitionUpgrade20Command.php#L24 "Oro\Bundle\WorkflowBundle\Command\DefinitionUpgrade20Command")</sup>
   - `CallBackTranslationGenerator`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/CallBackTranslationGenerator.php#L5 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\CallBackTranslationGenerator")</sup>
   - `ConfigFile`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/ConfigFile.php#L7 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\ConfigFile")</sup>
   - `ConfigResource`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/ConfigResource.php#L9 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\ConfigResource")</sup>
   - `GeneratedTranslationResource`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/GeneratedTranslationResource.php#L5 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\GeneratedTranslationResource")</sup>
   - `KeysUtil`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/KeysUtil.php#L7 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\KeysUtil")</sup>
   - `MovementOptions`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/MovementOptions.php#L7 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\MovementOptions")</sup>
   - `TranslationFile`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/TranslationFile.php#L10 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\TranslationFile")</sup>
   - `TranslationsExtractor`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/TranslationsExtractor.php#L9 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\TranslationsExtractor")</sup>
   - `YamlContentUtils`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/YamlContentUtils.php#L7 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\YamlContentUtils")</sup>
   - `WorkflowTranslationTools`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/Workflow/WorkflowTranslationTools.php#L15 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\Workflow\WorkflowTranslationTools")</sup>
   - `WorkflowsUtil`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/Workflow/WorkflowsUtil.php#L7 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\Workflow\WorkflowsUtil")</sup>
* The `ResourceTranslationGenerator`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/WorkflowBundle/Command/Upgrade20/ResourceTranslationGenerator.php#L5 "Oro\Bundle\WorkflowBundle\Command\Upgrade20\ResourceTranslationGenerator")</sup> interface was removed.

