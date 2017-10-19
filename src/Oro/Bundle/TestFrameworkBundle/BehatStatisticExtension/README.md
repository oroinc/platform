# Behat Statistic Extension

Easy way to store behat build statistic in database

### Configuration

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
            branch_name_env: BRANCH_NAME
            target_branch_env: CHANGE_TARGET
            build_id_env: BUILD_ID
```

To see all existing configuration abilities, follow Doctrine Dbal documentation -
http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html

```branch_name_env```, ```target_branch_env``` and ```build_id_env```
environment variables name. If not set or not exists it will be null.

### Usage

Use ```-f statistic``` formatter argument to enable store statistic to database:
```bash
bin/behat -f statistic
```
