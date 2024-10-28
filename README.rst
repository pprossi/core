========================
TYPO3 extension ``core``
========================

Get package manager to respect composer standard compliant options.

As packagemamager overrides hardcoded manifest name for __root__ level with composer.json it is'nt possible to use different manifests.

Allows to use multiple composer.json files at root level depending on environment settings.

see: https://getcomposer.org/doc/03-cli.md#composer

 composer.json

 composer-dev.json

The generated lock file will use the same name: composer-dev.lock in this example.

With this patch you are able to use commandline option to switch between composer files with;

 > COMPOSER=composer-dev.json composer install

get mor informations about loading of composer Files with:

 > TYPO3COMPOSERVERBOSE=true COMPOSER=composer-dev.json composer install

You can also set the environment on system level if you want to use a specific composer file in automated environments.

 COMPOSER=composer-dev.json
 
 TYPO3COMPOSERVERBOSE=true

and call

 > composer install

Patches:

Typo3 12

==== ============================================================== =====================================================================================
12.4 Packagemanager Patch composer standard compliant root manifest https://github.com/pprossi/core/commit/99dcf75b6ac26405f8126f99132b6b74981005ea.patch
==== ============================================================== =====================================================================================

Requirements:

Install composer Patches Package.

 > composer install cweagans/composer-patches --save-dev

and add to your extras section of your composer file:

.. code-block:: json

  {
    "require" : {
      ...
    },
    "extra" : {
      "patches" : {
        "typo3/cms-core" : {
          "Fix Composer STD Compilant PackageManager" : "https://github.com/pprossi/core/commit/99dcf75b6ac26405f8126f99132b6b74981005ea.patch"
        }
      }
    }
  }

