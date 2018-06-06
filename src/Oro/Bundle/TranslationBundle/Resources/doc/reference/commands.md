Translation workflow commands
=============================

Table of contents
----------------

- [Overview](#overview)
- [oro:translation:dump](#orotranslationdump)
- [oro:translation:pack](#orotranslationpack)
- [oro:translation:load](#orotranslationload)
- [oro:language:update](#orolanguageupdate)

Overview
----------

These are console commands that allows to generate, download and update translations data in system with third-party translation provider service. They can be run with bin/console command_name as usual.
Supported translation service adapters:

- Crowdin translation adapter

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

- `path` - Dump destination (or upload source), relative to `%kernel.project_dir%`, default is `/var/language-pack/`

- `dump` - action flag, used to scan project, find all translatable string and dump them in `path`

- `upload` - another action flag, perform upload to third party service

- `download` - perform download to third party service, downloads all language packs from project at translation service for specified `locale`

- `skipCheck` - Skip checking for the presence of the dump files of keywords without translation before upload/update.

Examples
------------------

**See command help:**
```bash
bin/console oro:translation:pack --help
```

**Download and apply translation pack:**
```bash
bin/console oro:translation:pack -i project-key -k abc1234567890c23ee33a767adb --download OroCRM

```

**Dump (generate) translation pack:**
```bash
bin/console oro:translation:pack --dump OroCRM
```

**Upload translation pack:**
Note: you must call dump command before using this one, otherwise system won't have anything to upload or will upload earlier generated files if there were left.
```bash
bin/console oro:translation:pack -i project-key -k abc1234567890c23ee33a767adb --upload OroCRM
```

oro:translation:load
--------------------
Command  used to load translations data to DB.
Params are:

- `languages` - the list of languages, that should be loaded.

- `rebuild-cache` - rebuild translation cache before and after loading.

**Load translations for English and Russian with rebuilding translation cache:**
```bash
bin/console oro:translation:load --languages=en --languages=ru --rebuild-cache
```

oro:language:update
--------------------
Command  used to load translations data from CROWDIN service to DB.
Params are:

- `language` - exact language code to be installed/updated.

- `all` - update/install all application's languages.

**Load translations for Russian:**
```bash
bin/console oro:language:update --language=ru_RU
```
**Load translations for all installed languages:**
```bash
bin/console oro:language:update --all
```

**List all installed languages:**
```bash
bin/console oro:language:update
```
