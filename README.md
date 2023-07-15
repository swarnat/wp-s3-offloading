WP S3 Offloading
================

> :warning: This currently is a WIP and not for production usage

This plugin provide functions to upload media directly or delayed to a s3 storage and replace the media URL with url from s3 storage.  
The main purpose is the automated deployment in Kubernetes/OpenShift of Docker environments, where a atateless system is helpfull.

This Plugin don't use any configuration UI, because it is used for automated deployment. You need to configure the following configuration variables in wp-config.php

```php
// your access key
define('WPS3_KEY', 'custom');
// your secret key to access bucket
define('WPS3_SECRET', 'custom');

// name of the bucket, you want to connect to
define('WPS3_BUCKET', 'custom');
// region of bucket
define('WPS3_REGION', 'custom');
// The folder within bucket to store files into
define('WPS3_FOLDER', 'custom');
// Should S3 access use pathstyle urls
define('WPS3_PATHSTYLE', 'custom');

// URL to access the uploaded assets
define('WPS3_URL_PREFIX', 'custom');
// Define a custom URL endpoint
define('WPS3_ENDPOINT', 'custom');
```