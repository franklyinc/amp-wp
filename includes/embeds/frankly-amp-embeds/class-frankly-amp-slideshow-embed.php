<?php
/**
 * Class Frankly_AMP_Slideshow_Embed_Handler
 *
 */

/**
 * Class Frankly_AMP_Slideshow_Embed_Handler
**/
class Frankly_AMP_Slideshow_Embed_Handler extends AMP_Base_Embed_Handler {
	public function register_embed() {
		return true;
	}

	public function unregister_embed() {
		return true;
	}

    /**
	 * Sanitized <div class="fb-video" data-href=> tags to <amp-facebook>.
	 *
	 * @param DOMDocument $dom DOM.
	 */
	public function sanitize_raw_embeds( $dom ) {
		/**
		 * Node list.
		 *
		 * @var DOMNodeList $node
		 */
		$nodes     = $dom->getElementsByTagName("div");
		$num_nodes = $nodes->length;

		if ( 0 === $num_nodes ) {
			return;
		}
		for ( $i = $num_nodes - 1; $i >= 0; $i-- ) {
			$node = $nodes->item( $i );
			if ( ! $node instanceof DOMElement ) {
				continue;
			}
			
			$class = $node->getAttribute( 'class' );
			
			if(stristr($class, 'frankly-slideshow')){
				$comment = $dom->createComment("Slideshow is not supported for AMP page");
				$node->parentNode->replaceChild( $comment, $node );
				$this->did_convert_elements = true;
			}
		}
	}
}

?>