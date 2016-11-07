Currently, WorkflowBundle supports translation functionality and each workflow (in part of its text fields) can be translated into multiple languages. So a developer might be curious about how to deal with that in a case of creating new workflow configuration or updating existing one.

So let's consider simple three steps that might bring relief to a workflow creation process.

***First Step***:

First of all, you should have your workflow configuration itself loaded, those are placed under `<YourBundle>/Resources/config/oro/workflows.yml` file and can be loaded by `oro:workflow:definitions:load` command.
(links: the configuration reference)

*For example*:
```bash
app/console oro:workflow:definitions:load --directories=$YOUR_BUNDLE_DIR/Resources/config/oro
```

***Second Step***:

After your valid configuration is ready you should add translations or user-friendly text representations of configuration pieces. Workflow translations can be loaded from theirs translation files placed under `<YourBundle>/Resources/translations/workflows.{lang}.yml` file (the same behavior as `messages.{lang}.yml` in Symfony defaults). For that purpose, to fill valid keys with translation text, you can use special command `oro:workflow:translations:dump` that might dump all related to your workflow translation keys to output (stdout) and can be used to build a `workflow.{lang}.yml` file.

*For example*, you have workflow named "my_workflow" and creating a file directly by redirecting output of command to a file:
```bash
app/console oro:workflow:translations:dump my_workflow --locale=en > $YOUR_BUNDLE_DIR/Resources/translations/workflows.en.yml
```
Now, file <YourBundleDirectory>/Resources/translations/workflows.en.yml will be filled by translation keys tree with empty strings, so a developer can fill their values with proper text (English in our case).

***Third step***:

When translation file was updated you might need to load translations into the system from that file. It can be performed by `oro:translation:load` command by simply running:
```bash 
app/console oro:translation:load
```

Now, if you need to **update** an existing workflow you can perform same operations because dumped translations of `oro:workflow:translations:dump` would be filled by existing one and newly created nodes of text.
To fully customize (replace config nodes, rename them) you can aways dump output of command elsewhere to be able manually choose what to update.



   