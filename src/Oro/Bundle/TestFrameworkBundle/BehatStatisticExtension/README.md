# Behat Statistic Extension

Easy way to store behat build statistic in database

## Configuration

```yaml
default: &default
    extensions: &default_extensions
        Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\BehatStatisticExtension:
            connection:
                dbname: dev_behat_stats
                user: dev
                password: 123456
                host: localhost
                driver: pdo_mysql
            criteria:
                branch_name: BRANCH_NAME
                target_branch: CHANGE_TARGET
                build_id: BUILD_ID
```

To see all existing configuration abilities, follow Doctrine Dbal documentation -
http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html

```branch_name```, ```target_branch``` and ```build_id```
environment variables name. If not set or not exists it will be null.

## Usage

Use ```-f statistic``` formatter argument to enable store statistic to database:
```bash
bin/behat -f statistic
```

### Tests

To run behat tests for extension install dev dependencies and run:

```bash
bin/behat
```

### Behat suite registry

This extension replace ```Behat\Testwork\Suite\Cli\SuiteController``` and
add new object ```SuiteConfigurationRegistry``` because list of suites
generates according to parameters ```--suite-divider```, ```--suite-set-divider``` and ```--max_suite_set_execution_time```


