kitsune
=======

(previously named as phpfox)

Emulates browser behavior: sends some default headers, referer and cookies. Useful for parsing private sections of websites.

Usage:
========

```php
<?php
$fox = new kitsune();
$fox->setGlobalHeader('User-Agent', 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.21 (KHTML, like Gecko) Chrome/25.0.1349.2 Safari/537.21');
$fox->get($url);
$fox->setHeader('X-CSRFToken', 'somevalue');
$fox->post($url, $postvars);

```
