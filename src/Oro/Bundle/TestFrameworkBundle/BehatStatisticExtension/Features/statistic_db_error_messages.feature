Feature: Statistic db error messages
  In order to be notified if something went wrong
  As a developer
  I should pass my tests without exception and see errors in console

  Background:
    Given enabled suites in behat.yml:
      """
      ping_pong:
          contexts:
              - Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\TestApp\PingPongContext
          paths: [ Features/ping_pong.feature]
      """

  Scenario: No error message if extension not enabled
    When I run "behat -s ping_pong"
    Then it should pass without:
      """
        Error while connectin to statistic DB
      """

  Scenario: Error message when db does not exists
    Given enabled Extension in behat.yml:
      """
      Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\BehatStatisticExtension:
        connection:
          dbname: mydb
          user: user
          password: secret
          host: localhost
          driver: pdo_mysql
      """
    When I run "behat -s ping_pong -vvv"
    Then it should pass with:
      """
      1 scenario (1 passed)
      2 steps (2 passed)
      """
    And the output should contain:
      """
      Error while connection to statistic DB
      """

  Scenario: Db exists but schema doesn't
    Given enabled Extension in behat.yml:
      """
      Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\ServiceContainer\BehatStatisticExtension:
        connection:
          driver: pdo_sqlite
          path: test.db
        criteria:
          branch_name: CHANGE_BRANCH_TEST
          target_branch: CHANGE_TARGET_TEST
          build_id: BUILD_ID_TEST
      """
    And "test.db" sqlite database exists
    When I run "behat -s ping_pong -f statistic -o std -f pretty -o std"
    Then it should pass without:
      """
      Error while connection to statistic DB
      """
    And the output should contain:
      """
      1 scenario (1 passed)
      2 steps (2 passed)
      """
    And the output should contain:
      """
      Exception while record the statistics:
      An exception occurred while executing 'INSERT INTO feature_stat
      """
