# Mage2 Module Emizentech Revelup

    ``emizentech/module-revelup``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities


## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Emizentech`
 - Enable the module by running `php bin/magento module:enable Emizentech_Revelup`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require emizentech/module-revelup`
 - enable the module by running `php bin/magento module:enable Emizentech_Revelup`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

 - api_endpoint (general/genetal/api_endpoint)

 - api_key (general/genetal/api_key)

 - api_pass (general/genetal/api_pass)


## Specifications

 - Cronjob
	- emizentech_revelup_customer

 - Cronjob
	- emizentech_revelup_order

 - Cronjob
	- emizentech_revelup_product

 - Cronjob
	- emizentech_revelup_category

 - Helper
	- Emizentech\Revelup\Helper\Data

 - Console Command
	- CustomerImport


## Attributes



