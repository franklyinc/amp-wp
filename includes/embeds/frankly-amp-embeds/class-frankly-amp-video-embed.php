<?php
/**
 * Class Frankly_AMP_Video_Embed_Handler
 **/
class Frankly_AMP_Video_Embed_Handler extends AMP_Base_Embed_Handler
{
    const URL_PATTERN = '';

    public function register_embed()
    {
        return true;
    }

    public function unregister_embed()
    {
        return true;
    }

    /**
     * Sanitized tags with video to <amp-video>.
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

			$data_mml_type = $node->getAttribute("data-mml-type");
			$data_oembed_url = $node->getAttribute("data-oembed-url");

			if ($data_mml_type !== null && $data_mml_type == "clip") {
				$iframe = $node->getElementsByTagName("iframe")->item(0);
				if($iframe == null) return;
				
				$src = $iframe->getAttribute("src");

                if ($src !== null) {
					if(substr( $src, 0, 2 ) === "//"){
						$src = substr_replace($src,"https://", 0, 2);
					};
					$src_parts = parse_url($src);
					$new_src = "[CONTENT_DOMAIN]".$src_parts["path"]."?".$src_parts["query"];

                    $this->create_amp_video_iframe_and_replace_node($dom, $node, $new_src);
                }
			};
			
			if ($data_oembed_url !== null) { 
				if(stripos($data_oembed_url,".mp4") || stripos($data_oembed_url,".webm") || stripos($data_oembed_url,".m3u8")) {
					$this->create_amp_video_and_replace_node($dom, $node, $data_oembed_url);
				}
			};
		}
	}

    private function create_amp_video_and_replace_node($dom, $node, $src)
    {
        $amp_video_node = AMP_DOM_Utils::create_node($dom, "amp-video", array(
			'src' => $src,
            'layout' => 'responsive',
            'width' => $this->DEFAULT_WIDTH,
			'height' => $this->DEFAULT_HEIGHT
        ));

        $wrapper_node = AMP_DOM_Utils::create_node($dom, "div", array(
            'class' => "frn-ampbody__embed frn-ampbody__video",
        ));

        $wrapper_node->appendChild($amp_video_node);

        $node->parentNode->replaceChild($wrapper_node, $node);

        $this->did_convert_elements = true;
	}
	
	private function create_amp_video_iframe_and_replace_node($dom, $node, $src)
    {
        $amp_video_node = AMP_DOM_Utils::create_node($dom, "amp-iframe", array(
			'src' => $src,
			'allow' =>'fullscreen',
			'layout' => 'responsive',
			'sandbox' => 'allow-same-origin allow-scripts',
            'width' => $this->DEFAULT_WIDTH,
			'height' => $this->DEFAULT_HEIGHT
        ));

        $wrapper_node = AMP_DOM_Utils::create_node($dom, "div", array(
            'class' => "frn-ampbody__embed frn-ampbody__video-iframe",
        ));

        $wrapper_node->appendChild($amp_video_node);

        $node->parentNode->replaceChild($wrapper_node, $node);

        $this->did_convert_elements = true;
    }

}
