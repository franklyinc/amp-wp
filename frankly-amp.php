<?php

# SUPER HACK!! Include all the stubs/copies of internal wordpress functions since we're running outside the wordpress environment
require_once "includes/utils/frankly-amp-utils.php";

# Call the basic initialization done by default in the original module. This registers all the sanitizers, etc..
# Requires the frankly-amp-utils above for access to wordpress functions.
require_once "amp.php";

$html = file_get_contents('php://input');
$amp_content = new AMP_Content(
   $html,
   amp_get_content_embed_handlers($html),
   amp_get_content_sanitizers($html),
   array(
     'content_max_width' => 400 ,
   )
 );

echo $amp_content->get_amp_content();
