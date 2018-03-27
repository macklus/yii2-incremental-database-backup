Yii2 incremental, compressed and versioned database backup extension
====================================================================
Dump selected databases, compare md5sum for compressed files and update git repository for a safe and comprehensive backup

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist macklus/yii2-incremental-database-backup "*"
```

or add

```
"macklus/yii2-incremental-database-backup": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \macklus\backup\AutoloadExample::widget(); ?>```