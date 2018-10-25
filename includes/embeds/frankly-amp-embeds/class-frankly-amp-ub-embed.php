<?php
/**
 * Class Frankly_AMP_UB_Embed_Handler
 **/
class Frankly_AMP_UB_Embed_Handler extends AMP_Base_Embed_Handler
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
     * Sanitized tags with data-mml-type="code". Utility Blocks are not supported for amp so have been removed 
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

            if ($data_mml_type !== null && $data_mml_type == "code") {
				$comment = $dom->createComment("Utility Block is not supported for AMP page");
				$node->parentNode->replaceChild( $comment, $node );
				$this->did_convert_elements = true;
			};
		}
	}
}
