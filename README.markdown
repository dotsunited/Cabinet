Cabinet
=======

[![Build Status](https://travis-ci.org/dotsunited/Cabinet.svg?branch=master)](http://travis-ci.org/dotsunited/Cabinet)

Cabinet is a PHP 5.3+ library providing a simple file storage layer.

It provides a unified API for storing and retrieving files as well as getting basic informations like size and content type of a file.

This is useful if

  * you want to write reusable components with a configurable file storage backend
  * you want to ensure scalability for your file storage (You can for example start storing your files on your servers filesystem and switch later to Amazon S3)

Cabinet offers adapters for [PHP streams](http://php.net/stream) and [Amazon S3](https://s3.amazonaws.com) out of the box. But you can easily write your own adapters by implementing `DotsUnited\Cabinet\Adapter\AdapterInterface`.

Installation
------------

Cabinet can be installed using the [Composer](http://packagist.org) tool. You can either add `dotsunited/cabinet` to the dependencies in your composer.json, or if you want to install Cabinet as standalone, go to the main directory and run:

```bash
$ wget http://getcomposer.org/composer.phar
$ php composer.phar install
```

You can then use the composer-generated autoloader to access the Cabinet classes:

```php
<?php
require 'vendor/autoload.php';
?>
```

Usage
-----

Instances of Cabinet adapters can be either created directly or using the static `DotsUnited\Cabinet\Cabinet::factory()` method.

### Using a Cabinet Adapter constructor ###

You can create an instance of an adapter using its constructor. An adapter constructor takes one argument, which is an array of configuration parameters.

```php
<?php
$adapter = new \DotsUnited\Cabinet\Adapter\StreamAdapter(array(
    'base_path' => '/my/base/path',
    'base_uri'  => 'http://static.example.com'
));
?>
```

### Using the Cabinet factory ###

As an alternative to using an adapter constructor directly, you can create an instance of an adapter using the static method `DotsUnited\Cabinet\Cabinet::factory()`.

The first argument is a string that names the adapter class (for example '\DotsUnited\Cabinet\Adapter\StreamAdapter'). The second argument is the same array of parameters you would have given to the adapter constructor.

```php
<?php
$adapter = \DotsUnited\Cabinet\Cabinet::factory('\DotsUnited\Cabinet\Adapter\StreamAdapter', array(
    'base_path' => '/my/base/path',
    'base_uri'  => 'http://static.example.com'
));
?>
```

Alternatively, the first argument can be an associative array. The adapter class is then read from the 'adapter' key. Optionally, it can contain a 'config' key holding the configuration parameters. In this case, the second argument will be ignored.

```php
<?php
$adapter = \DotsUnited\Cabinet\Cabinet::factory(array(
    'adapter' => '\DotsUnited\Cabinet\Adapter\StreamAdapter',
    'config' => array(
        'base_path' => '/my/base/path',
        'base_uri'  => 'http://static.example.com'
    )
));
?>
```

### Managing files ###

Once you created the adapter, you can store, retrieve and get informations about files with the following methods:

#### Import a external local file: ####
```php
<?php
$adapter->import($external, $file);
?>
```

#### Write data to a file: ####
```php
<?php
$adapter->write($file, $data);
?>
```

#### Read data from a file: ####
```php
<?php
$adapter->read($file);
?>
```

#### Get a read-only stream resource for a file: ####
```php
<?php
$adapter->stream($file);
?>
```

#### Copy a file internally: ####
```php
<?php
$adapter->copy($src, $dest);
?>
```

#### Rename a file internally: ####
```php
<?php
$adapter->rename($src, $dest);
?>
```

#### Delete a file: ####
```php
<?php
$adapter->unlink($file);
?>
```

#### Check whether a file exists: ####
```php
<?php
$adapter->exists($file);
?>
```

#### Get a files size: ####
```php
<?php
$adapter->size($file);
?>
```

#### Get a files MIME content type: ####
```php
<?php
$adapter->type($file);
?>
```

#### Get the web-accessible uri for the given file: ####
```php
<?php
$adapter->uri($file);
?>
```

Adapters
--------

Cabinet offers two adapters:

  * `DotsUnited\Cabinet\Adapter\StreamAdapter` for [PHP streams](http://php.net/stream)
  * `DotsUnited\Cabinet\Adapter\AmazonS3Adapter` for [Amazon S3](https://s3.amazonaws.com)

Each adapter accepts its own set of configuration parameters which can be passed as an associative array to the constructor.

### DotsUnited\Cabinet\Adapter\StreamAdapter ###

Configuration parameters for `DotsUnited\Cabinet\Adapter\StreamAdapter`:

  * `base_path`:
     Path where to store the files.
  * `base_uri`:
     Uri where your files are publicly accessible.
  * `directory_umask`:
    The umask for directories created by the adapter (default is 0700).
  * `file_umask`:
    The umask for files created by the adapter (default is 0600).
  * `stream_context`:
    The [stream context](http://php.net/stream.contexts) to use with filesystem functions. This can be either a resource created with [`stream_context_create()`](http://php.net/stream-context-create) or an array with [context options](http://php.net/context).
  * `mime_type_detector`:
    An instance of `DotsUnited\Cabinet\MimeType\Detector\DetectorInterface` used to detect mime content types. This is optional, default is `DotsUnited\Cabinet\MimeType\Detector\Fileinfo`.
  * `filename_filter`:
    An instance of `DotsUnited\Cabinet\Filter\FilterInterface`. Filename filters are explained in the next section.

### DotsUnited\Cabinet\Adapter\AmazonS3Adapter ###

Configuration parameters for `DotsUnited\Cabinet\Adapter\AmazonS3Adapter`:

  * `s3_client`:
    A `Aws\S3\S3Client` instance (See the [AWS SDK docs](http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-s3.html#creating-a-client)).
  * `bucket`:
    Bucket where to store the files.
  * `storage_class`:
    The storage class setting for files. Allowed values: `STANDARD`, `REDUCED_REDUNDANCY`. The default value is `STANDARD`.
  * `acl`:
    The ACL settings for files. Allowed values: `private`, `public-read`, `public-read-write`, `authenticated-read`, `bucket-owner-read`, `bucket-owner-full-control`. The default value is `private`.
  * `uri_expiration_time`:
    The expiration time for web-accessible URIs if you store private files. This can bei either a timestamp or a string parsable by `strtotime()`.
  * `throw_exceptions`:
    A boolean indicating whether to throw exceptions (Exceptions from the `Aws\S3\S3Client` are catched and rethrown as `\RuntimeException`).
  * `mime_type_detector`:
    An instance of `DotsUnited\Cabinet\MimeType\Detector\DetectorInterface` used to detect mime content types. This is optional, default is `DotsUnited\Cabinet\MimeType\Detector\Fileinfo`.
  * `filename_filter`:
    An instance of `DotsUnited\Cabinet\Filter\FilterInterface`. Filename filters are explained in the next section.

Filename filters
----------------

You can manipulate the filename you pass to each method of an adapter with filters. Filters are classes which implement `DotsUnited\Cabinet\Filter\FilterInterface`.

You can add filters like this:

```php
<?php
$adapter->setFilenameFilter(new MyFilenameFilter());
?>
```

If you need multiple filters, you can use `DotsUnited\Cabinet\Filter\FilterChain` like this:

```php
<?php
$filterChain = new \DotsUnited\Cabinet\Filter\FilterChain();

$filterChain->addFilter(new MyFilenameFilter1());
$filterChain->addFilter(new MyFilenameFilter2());

$adapter->setFilenameFilter($filterChain);
?>
```

### Examples ###

Cabinet provides a filter to prepend a hashed subpath to the filename and is intended to be used with the `DotsUnited\Cabinet\Adapter\StreamAdapter` adapter.

This spreads the files over subdirectories to ensure performance by avoiding to many files in one directory. This is done by using a configurable number of characters of the filename's MD5 as a directory name. That pretty much guarantees an even spread.

Simply register the `DotsUnited\Cabinet\Filter\HashedSubpathFilter` filter with the adapter:

```php
<?php
$config = array(
    'level' => 4
);

$adapter->setFilenameFilter(new \DotsUnited\Cabinet\Filter\HashedSubpathFilter($config);
?>
```

License
-------

Cabinet is released under the [New BSD License](https://github.com/dotsunited/Cabinet/blob/master/LICENSE).
