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
$html = "<p>State parks officials are putting extra eyes on beaches with additional lifeguards after two apparent shark bites Wednesday off Fire Island.</p><p>All Islip town beaches reopened Thursday, as well as beaches at Sailors Haven and Watch Hill.</p><p>Lola Pollina says she'd only ventured a few feet into the water when something grabbed her leg around noon yesterday.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p><blockquote><p>&quot;When I was in the water, I kind of saw this tan, orangey looking body and a small fin and when I got out my leg was bloody and I saw scratch marks,&quot; says Pollina.</p></blockquote><p><img alt=\"\" src=\"https://NEWS12HV.images.worldnow.com/images/17229469_G.jpg\" style=\"height:360px; width:640px\" /></p><p>Pollina has serious, but non-life-threatening bite wounds that require her to have surgery today.</p><div data-oembed-url=\"https://twitter.com/News12LI/status/1019970452771561472\"><blockquote align=\"center\" class=\"twitter-tweet\" data-dnt=\"true\"><p dir=\"ltr\" lang=\"en\">UPDATE: Another shark caught by fisherman off Fire Island beach. <a href=\"https://t.co/fhP5zzSFJd\">https://t.co/fhP5zzSFJd</a></p>&mdash; News12LI (@News12LI) <a href=\"https://twitter.com/News12LI/status/1019970452771561472?ref_src=twsrc%5Etfw\">July 19, 2018</a></blockquote><script async=\"\" charset=\"utf-8\" src=\"https://platform.twitter.com/widgets.js\"></script></div><p>The 13-year-old boy was boogie boarding at his summer camp when he fell off the board and was bitten. A tooth was removed from a puncture wound in his leg.</p><p><iframe height=\"100px\" src=\"https://docs.google.com/gview?url=https://news12hv.images.worldnow.com/library/183fc214-11c8-468b-b628-abe30574004a.pdf&amp;embedded=true\" width=\"100%\"></iframe></p><p>Islip Town officials say two sharks were caught by fishermen in the Atlantic Ocean Wednesday afternoon. One was caught 50 yards east of Atlantique. The other was at Kismet Beach. There was no confirmation that those sharks were involved in the two attacks.</p><div data-oembed-url=\"https://www.facebook.com/News12LI/posts/10156124447013551\"><div class=\"iframely-embed\" style=\"max-width: 640px;\"><div class=\"iframely-responsive\" style=\"padding-bottom: 100%;\"><a data-iframely-url=\"//cdn.iframe.ly/BZODTAb\" href=\"https://www.facebook.com/News12LI/posts/10156124447013551\">News 12 Long Island</a></div></div><script async=\"\" charset=\"utf-8\" src=\"//cdn.iframe.ly/embed.js\"></script></div><p>Gov. Andrew Cuomo released a statement saying that he has deployed Department of Environmental Conservation Commissioner Basil Seggos to investigate the &quot;apparent juvenile shark attacks off of Fire Island.&quot;</p>";

$amp_content = new AMP_Content(
   $html,
   amp_get_content_embed_handlers($html),
   amp_get_content_sanitizers($html),
   array(
     'content_max_width' => 400 ,
   )
 );

echo $amp_content->get_amp_content();
