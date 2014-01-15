Translation workflow commands
=============================

Table of contents
----------------

- [Overview](#overview)
- [oro:translation:dump](#orotranslationdump)
- [oro:translation:pack](#orotranslationpack)
- [Examples](#examples)

Overview
----------

These are console commands that allows to generate, download and update translations data in system with third-party translation provider service. They can be run with app/console command_name as usual.
Two supported translation service adapters available:

- Crowdin translation adapter
- [Oro translation service proxy](https://github.com/laboro/translation-proxy)

oro:translation:dump
-------------
Generate translation data for application's JavaScript (frontend) layer and dump it to web public folder under js/translations.
Params are:

- `debug` - flag to dump js-translation resources with debug mode
- `locale` - locale to generate

oro:translation:pack
------------------
Command used to dump, upload, download and update translations data on third-party translation services (currently Crowdin supported). Required params marked with *.

- `project`* - project name, e.g. Acme, Oro, OroCRM, usually it's your namespace under src.

- `locale` - locale to process, default is `en`

- `adapter` - optional parameter, default is crowdin, allows to use non-default adapter service, this value used to compose adapter service name `oro_translation.uploader.%s_adapter`, so it will look for `oro_translation.uploader.crowdin_adapter` in service container.

- `project-id` or `i` - API project id

- `api-key` or `k` - API key

- `upload-mode` or `m` - upload mode, supported values: `add`, `update`, default is `add`. Update mode will first download existring translation source from remote service, then will merge it with existing, previously generated with `--dump` and upload it back.

- `output-format` - output format for translation files, default is yml.

- `path` - Dump destination (or upload source), relative to `%kernel.root_dir%`, default is `/Resources/language-pack/`

- `dump` - action flag, used to scan project, find all translatable string and dump them in `path`

- `upload` - another action flag, perform upload to third party service

- `download` - perform download to third party service, downloads all language packs from project at translation service for specified `locale`

Examples
------------------

**See command help:**
```bash
app/console oro:translation:pack --help
```

**Download and apply translation pack:**
```bash
app/console oro:translation:pack -i project-key -k abc1234567890c23ee33a767adb --download OroCRM

```

**Dump (generate) translation pack:**
```bash
app/console oro:translation:pack --dump OroCRM
```

**Upload translation pack:**
Note: you must call dump command before using this one, otherwise system won't have anything to upload or will upload earlier generated files if there were left.
```bash
app/console oro:translation:pack -i project-key -k abc1234567890c23ee33a767adb --upload OroCRM
```
