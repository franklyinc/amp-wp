<?php
/**
 * Class Frankly_AMP_Facebook_Embed_Handler
 *
 */

/**
 * Class Frankly_AMP_Facebook_Embed_Handler
 **/
class Frankly_AMP_Facebook_Embed_Handler extends AMP_Base_Embed_Handler
{
    const URL_PATTERN = '#https?://(www\.)?facebook\.com/.*#i';

    public function register_embed()
    {
        return true;
    }

    public function unregister_embed()
    {
        return true;
    }

    private function get_embed_type($string)
    {
        if (false !== strpos($string, 'post')) {
            if (false !== strpos($string, 'comment_id')) {
                return 'comment';
            } else {
				return 'post';
			}

        } elseif (false !== strpos($string, 'video')) {
            return 'video';
        }
    }

    /**
     * Sanitized facebook tags created in Classic and NextGen Story editor to <amp-facebook>.
     *
     * @param DOMDocument $dom DOM.
     */
    public function sanitize_raw_embeds($dom)
    {
        /**
         * Node list.
         *
         * @var DOMNodeList $node
         */
        $nodes = $dom->getElementsByTagName("div");
        $num_nodes = $nodes->length;

        if (0 === $num_nodes) {
            return;
        }

        for ($i = $num_nodes - 1; $i >= 0; $i--) {
            $node = $nodes->item($i);
            if (!$node instanceof DOMElement) {
                continue;
            }

            $data_oembed_url = $node->getAttribute('data-oembed-url');

            if ($data_oembed_url !== null) {
                preg_match(self::URL_PATTERN, $data_oembed_url, $matches);

                if ($matches[0] !== null) {
                    $embed_type = $this->get_embed_type($matches[0]);
                    $this->create_amp_facebook_and_replace_node($dom, $node, $embed_type, $matches[0]);
                }
            }
        }
    }

    private function create_amp_facebook_and_replace_node($dom, $node, $embed_type, $href)
    {
        $amp_facebook_node = AMP_DOM_Utils::create_node($dom, "amp-facebook", array(
            'data-href' => $href,
            'data-embed-as' => $embed_type,
            'layout' => 'responsive',
            'width' => $this->DEFAULT_WIDTH,
            'height' => $this->DEFAULT_HEIGHT,
        ));

        $wrapper_node = AMP_DOM_Utils::create_node($dom, "div", array(
            'class' => "frn-ampbody__embed frn-ampbody__facebook",
        ));

        $wrapper_node->appendChild($amp_facebook_node);

        $node->parentNode->replaceChild($wrapper_node, $node);

        $this->did_convert_elements = true;
    }

}
