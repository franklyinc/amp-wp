<?php
/**
 * Class Frankly_AMP_Instagram_Embed_Handler
 *
 */

/**
 * Class Frankly_AMP_Instagram_Embed_Handler
 **/
class Frankly_AMP_Instagram_Embed_Handler extends AMP_Base_Embed_Handler
{
    const URL_PATTERN = '#http(s?)://(www\.)?instagr(\.am|am\.com)/p/([^/?]+)#i';

    public function register_embed()
    {
        return true;
    }

    public function unregister_embed()
    {
        return true;
    }

    /**
     * Sanitized Instagram tags created in NextGen Story editor to <amp-instagram>.
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

            $data_mml_uri = $node->getAttribute('data-mml-uri');

            if ($data_mml_uri !== null) {
                preg_match(self::URL_PATTERN, $data_mml_uri, $matches);

                if ($matches[0] !== null) {
                    $instagram_id = end($matches);
                    $this->create_amp_instagram_and_replace_node( $dom, $node, $instagram_id );
                }
            }
        }
    }

    private function create_amp_instagram_and_replace_node( $dom, $node, $instagram_id ) {
		$new_node = AMP_DOM_Utils::create_node( $dom, 'amp-instagram', array(
			'data-shortcode' => $instagram_id,
			'layout'         => 'responsive',
			'width'          => $this->DEFAULT_WIDTH,
			'height'         => $this->DEFAULT_HEIGHT,
        ) );
        
        $wrapper_node = AMP_DOM_Utils::create_node($dom, "div", array(
            'class' => "frn-ampbody__embed frn-ampbody__instagram"
        ));

        $wrapper_node->appendChild($new_node);

		$node->parentNode->replaceChild( $wrapper_node, $node );

		$this->did_convert_elements = true;
	}

}
