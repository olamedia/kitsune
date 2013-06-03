kitsune
=======

(previously named as phpfox)

Emulates browser behavior: sends some default headers, referer and cookies. Useful for parsing private sections of websites.

Usage:
========

```php
<?php
$fox = new kitsune();
$fox->get($url);
$fox->post($url, $postvars);

```
