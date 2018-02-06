BehatJsonFormatter
==================

[![Build Status](https://travis-ci.org/Seretos/BehatJsonFormatter.svg?branch=master)](https://travis-ci.org/Seretos/BehatJsonFormatter)

this library extend the behat library with an json formatter. this formatter generate for every suite an json format
the executed features,scenarios and steps.
the library also saved the executed environment (firefox,IE,...) result.
Every browserless environment will be saved as "unknown"

this library provides also different commands to validate and merge multiple generated json files.

Installation
------------

execute the following command as below
```bash
$ composer require seretos/BehatJsonFormatter
```

Usage
-----

add the following lines to your behat config yml
```yml
...
  extensions:
    seretos\BehatJsonFormatter\BehatJsonFormatterExtension:
      output_path: '%paths.base%/build/behat'
      #step_screenshots: true #save a screenshot for every selenium step
```

now your execution generates for every suite an json file into the output-path.
if you run multiple tests on different machines, you can use the following commands to manage your json results:

```bash
$ php vendor/bin/behat-json behat:double:result:check --jsonDir=./artifacts1 \
                                                      --jsonDir=./artifacts2 \
                                                      --pattern="/[\w]*php71/"
```
this command search in the given directories and with pattern for json files and check, that every test only executed one time per environment

```bash
php vendor/bin/behat-json behat:merge:result --jsonDir=./artifacts1 \
                                             --jsonDir=./artifacts2 \
                                             --pattern="/[\w]*php71/" \
                                             --output=result.json
```

```bash
php vendor/bin/behat-json behat:validate:result --json=./result.json \
                                                --featureDir=./features
```