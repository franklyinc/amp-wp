<?php

/*

Prior to running, install Composer: https://getcomposer.org/download/

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
./composer.phar install
php frankly-amp.php

*/

# SUPER HACK!! Include all the stubs/copies of internal wordpress functions since we're running outside the wordpress environment
require_once "includes/utils/frankly-amp-utils.php";

# Call the basic initialization done by default in the original module. This registers all the sanitizers, etc..
# Requires the frankly-amp-utils above for access to wordpress functions.
require_once "amp.php";

$html = "<img src='test'><img><a href='sdf'>testr</a><script type='text/javascript'>console.log('df');</script>";

$amp_content = new AMP_Content(
   $html,
   amp_get_content_embed_handlers($html),
   amp_get_content_sanitizers($html),
   array(
     'content_max_width' => 400 ,
   )
 );

echo $amp_content->get_amp_content();
