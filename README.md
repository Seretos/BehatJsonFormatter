BehatJsonFormatter
==================

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