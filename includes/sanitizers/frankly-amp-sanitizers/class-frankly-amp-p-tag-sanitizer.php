<?php
class Frankly_AMP_P_Tag_Sanitizer extends AMP_Base_Sanitizer {
	public function sanitize() {
		$p_tags = $this->dom->getElementsByTagName( 'p' );

		for ( $i = $p_tags->length - 1; $i >= 0; $i-- ) {
			$p_tag = $p_tags->item( $i );
			$val = $p_tag->nodeValue;
			$id = $p_tag->getAttribute("id");
			
			if(strpos($id, 'breakId_SPECIAL_') !== false){
				if ( AMP_DOM_Utils::is_node_empty( $p_tag ) || $p_tag->nodeValue == " " ) {
					$p_tag->parentNode->removeChild( $p_tag );
				}
			}
		}
	}
}
