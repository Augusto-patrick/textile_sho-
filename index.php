<?php
// Root of the site sends everyone to the public storefront.
// Staff/admin log in separately at /login.php.
header('Location: /textile_shop/storefront/index.php');
exit;
