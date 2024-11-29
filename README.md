# USC.GOV

This site ships with a number of utilities that can be accessed and used
inside the containers by running them with the prefix `lando`. For instance:

```
# Install composer dependencies
lando composer install

# Rebuild the Drupal cache with Drush
lando drush cr
```

One of the tools provided is the Robo task runner for PHP.
The project comes with a variety of Robo tasks in the `RoboFile.php`
file.

## Required Environment Variables.

```
DRUPAL_DB_USER=drupal10
DRUPAL_DB_NAME=drupal10
DRUPAL_DB_PASS=drupal10
DRUPAL_DB_HOST=database
DRUPAL_DB_PORT=3306

DRUPAL_REDIS_HOST=redis
DRUPAL_REDIS_PORT=6379

MIGRATION_DB_USER=mysql
MIGRATION_DB_NAME=database
MIGRATION_DB_PASS=mysql
MIGRATION_DB_HOST=migration
MIGRATION_DB_PORT=3306
```

## Lando Local Development

### Dependencies

  - [Docker](https://docs.docker.com/get-docker)
  - [Lando](https://docs.lando.dev/basics/installation.html)

### Installation Steps

  1. Clone the repository. Eg. `git clone path-to-repo uscourts`
  2. `cd uscourts`
  3. Copy and rename the file `example.lando.dev` as `.lando.dev`, it will serve for your local Environment Variables.
  4. `lando start`
  5. `lando robo local:init`

### Run Behat tests.

All the dependencies needed to run functional tests via Behat are already
installed on this project, to run one of the examples test do the following:

`lando behat features/dashboard.feature`

If you want to know the available Behat steps then run:

`lando behat -dl`

More information about Behat Drupal Extension can be found at https://behat-drupal-extension.readthedocs.io/en/v4.0.1/index.html

### Making use of phpstan for static analysis

## PHPCs

To run `phpcs` use the robo command: `lando robo phpcs` which will run `phpcs` on the codebase, excluding common
locations for third party code, and return a list of errors.

To run `phpcbf` use the robo command: `lando robo phpcbf` which will run `phpcbf` on the codebase and fix coding
standard errors.

Documentation for `phpcs` can be found at https://github.com/squizlabs/PHP_CodeSniffer
The settings are in the phpcs.xml file.

## PHPStan

To run `phpstan` use the robo command: `lando robo analyse` which will run `phpstan` on the codebase, excluding common
locations for third party code, and return a list of errors and suggestions for code improvement.

If you are introducing `phpstan` into an existing codebase and initially only want to analyse new code going forward
until technical debt can be addressed, run the `lando robo analyse:baseline` command to record all existing issues into
a `phpstan-baseline.neon` file. Then add this file to the includes section of `phpstan.neon.dist`.

Documentation for `phpstan` can be found at https://phpstan.org/.

### Setup secondary database on local environment

The environment comes with a `migration` service which is a secondary database and it is used as a migration source.
Run `lando db-import -h migration <path to a database backup file>` to import data from the legacy USCourts.gov backup.