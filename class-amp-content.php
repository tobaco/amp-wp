<?php

class AMP_Content {
	private $original_content;

	public function __construct( $content ) {
		$this->original_content = $content;
	}

	public function transform() {
		$content = $this->original_content;

		$content = apply_filters( 'the_content', $content );

		// Convert HTML to AMP
		// see https://github.com/ampproject/amphtml/blob/master/spec/amp-html-format.md#html-tags)

		$content = ( new AMP_Img_Converter )->convert( $content );

		return $content;
	}
}

abstract class AMP_Converter {
	abstract public function convert( $content );

	public function has_tag( $content, $tag ) {
		return false !== stripos( $content, sprintf( '<%s', $tag ) );
	}

	public function get_tags( $content, $tag ) {
		preg_match_all( '#<' . $tag . '([^>]+?)(></' . $tag . '>|[\/]?>)#i', $content, $tags, PREG_SET_ORDER );
		return $tags;
	}
}

class AMP_Img_Converter extends AMP_Converter {
	public static $tag = 'img';

	public function convert( $content ) {
		if ( ! $this->has_tag( $content, self::$tag ) ) {
			return $content;
		}

		$images = $this->get_tags( $content, self::$tag );
		if ( empty( $images ) ) {
			return $content;
		}

		foreach ( $images as $image ) {
			$old_img = $image[0];
			$old_img_attr = isset( $image[1] ) ? $image[1] : '';
			$new_img = '<amp-img';

			$attributes = wp_kses_hair( $old_img_attr,  array( 'http', 'https' ) );
			foreach ( $attributes as $attribute ) {
				$name = $attribute['name'];
				$value = $attribute['value'];

				switch ( $name ) {
					case 'src':
					case 'alt':
					case 'width':
					case 'height':
						$new_img .= sprintf( ' %s="%s"', $name, esc_attr( $value ) );
						break;
					default;
						break;
				}

			}

			$new_img .= '></amp-img>';

			$old_img_pattern = '~' . preg_quote( $old_img, '~' ) . '~';
			$content = preg_replace( $old_img_pattern, $new_img, $content, 1 );
		}

		return $content;
	}
}