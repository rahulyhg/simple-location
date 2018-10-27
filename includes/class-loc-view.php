<?php

add_action( 'init', array( 'Loc_View', 'init' ) );

// Location Display
class Loc_View {

	public static function init() {
		add_filter( 'get_comment_text', array( 'Loc_View', 'location_comment' ), 12, 2 );
		add_filter( 'the_content', array( 'Loc_View', 'content_map' ), 11 );
		if ( ! current_theme_supports( 'simple-location' ) ) {
			add_filter( 'the_content', array( 'Loc_View', 'location_content' ), 12 );
		}
	}

	public static function get_icon() {
		// Substitute another svg sprite file
		$sprite = plugins_url( 'location.svg', dirname( __FILE__ ) );
		return '<img class="icon-location" aria-label="' . __( 'Location: ', 'simple-location' ) . '" aria-hidden="true" src="' . $sprite . '" />';
	}

	public static function get_location( $object = null, $args = array() ) {
		$loc = WP_Geo_Data::get_geodata( $object );
		if ( ! isset( $loc ) || 'private' === $loc['visibility'] ) {
			return '';
		}
		$defaults = array(
			'height'        => null,
			'width'         => null,
			'map_zoom'      => null,
			'mapboxstyle'   => null,
			'mapboxuser'    => null,
			'weather'       => true,
			'icon'          => true, // Show Location Icon
			'text'          => false, // Show Description
			'description'   => __( 'Location: ', 'simple-location' ),
			'wrapper-class' => array( 'sloc-display' ), // Class or classes to wrap the entire location in
			'wrapper-type'  => 'p', // HTML type to wrap the entire location in
		);
		$default  = apply_filters( 'simple_location_display_defaults', $defaults );
		$args     = wp_parse_args( $args, $defaults );
		$args     = array_merge( $loc, $args );
		$map      = Loc_Config::map_provider();
		$map->set( $loc );
		$wrap  = '<%1$s class="%2$s">%3$s</%1$s>';
		$class = is_array( $args['wrapper-class'] ) ? implode( ' ', $args['wrapper-class'] ) : $args['wrapper-class'];
		$c     = '<span class="p-location">';

		if ( $args['text'] ) {
			$c .= $args['description'];
		}
		if ( 'public' === $args['visibility'] ) {
			$c             .= self::get_the_geo( $loc );
			$loc['address'] = isset( $loc['address'] ) ? $loc['address'] : dec_to_dms( $loc['latitude'], $loc['longitude'] );
			$c             .= sprintf( '<a href="%1$s">%2$s</a>', $map->get_the_map_url(), $args['address'] );
		} else {
			$c = isset( $args['address'] ) ? $args['address'] : '';
		}
		$c .= '</span>';
		if ( isset( $loc['weather'] ) && $args['weather'] ) {
			$c .= self::get_the_weather( $loc['weather'] );
		}
		if ( $args['icon'] ) {
			$c = self::get_icon() . $c;
		}
		return sprintf( $wrap, $args['wrapper-type'], $class, $c );
	}

	public static function get_map( $object = null, $args = array() ) {
		$loc = WP_Geo_Data::get_geodata( $object );
		if ( isset( $loc ) && ( 'public' === $loc['visibility'] ) ) {
			$map = Loc_Config::map_provider();
			$map->set( $loc );
			return $map->get_the_map();
		}
		return '';
	}

	public static function get_the_weather( $weather ) {
		if ( ! is_array( $weather ) || empty( $weather ) ) {
			return '';
		}
		if ( ! isset( $weather['icon'] ) ) {
			$weather['icon'] = 'wi-thermometer';
		}
		$units = ifset( $weather['units'] );
		if ( ! $units ) {
			switch ( get_option( 'sloc_measurements' ) ) {
				case 'imperial':
					$units = 'F';
					break;
				default:
					$units = 'C';
			}
		}
		$c = '<br />' . Weather_Provider::get_icon( $weather['icon'], ifset( $weather['summary'], '' ) );
		if ( isset( $weather['temperature'] ) ) {
			$c .= '<span class="p-temperature">' . round( $weather['temperature'] ) . '&deg;' . $units . '</span>';
		}
		return $c;
	}

	// Return marked up coordinates
	public static function get_the_geo( $loc, $display = false ) {
		if ( isset( $loc['latitude'] ) && isset( $loc['longitude'] ) ) {
			if ( $display ) {
				return sprintf(
					'<span class="p-latitude">%1$f</span>,
					<span class="p-longitude">%2$f</span>',
					$loc['latitude'],
					$loc['longitude']
				);
			} else {
				return sprintf(
					'<data class="p-latitude" value="%1$f"></data>
					<data class="p-longitude" value="%2$f"></data>',
					$loc['latitude'],
					$loc['longitude']
				);
			}
		}
		return '';
	}

	public static function location_content( $content ) {
		$loc = self::get_location();
		if ( ! empty( $loc ) ) {
			$content .= $loc;
		}
		return $content;
	}

	public static function location_comment( $comment_text, $comment ) {
		$loc = self::get_location(
			$comment,
			array(
				'text' => false,
				'icon' => false,
			)
		);
		if ( ! empty( $loc ) ) {
			$comment_text .= PHP_EOL . $loc . PHP_EOL;
		}
		return $comment_text;
	}

	public static function content_map( $content ) {
		if ( is_single() ) {
			$content .= self::get_map();
		}
		return $content;
	}

} // Class Ends

function get_simple_location( $object = null, $args = array() ) {
	Loc_View::get_location( $object, $args );
}


