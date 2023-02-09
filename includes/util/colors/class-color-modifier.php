<?php

namespace Gravity_Forms\Gravity_Forms\Util\Colors;

class Color_Modifier {

	const STEP_VALUE = 5;

	public function rgb_to_hsv( $r, $g, $b ) {
		// Convert to 0-1 ranges
		$r = $r / 255;
		$g = $g / 255;
		$b = $b / 255;

		// Get min, max, and delta
		$maxRGB = max( $r, $g, $b );
		$minRGB = min( $r, $g, $b );
		$delta = $maxRGB - $minRGB;

		// $v (value) as percentage
		$v = 100 * $maxRGB;

		// If chroma is zero, we don't need to calculate the other values. Return 0, 0, $v.
		if ( $delta == 0 ) {
			return array( 0, 0, $v );
		}

		// $s (saturation) as a percentage.
		$s = 100 * ( $delta / $maxRGB );

		// Calculate $h (hue) segment of 2D hexagonal plane based on maxRGB value
		if ( $r == $maxRGB ) {
			$h = ( $g - $b ) / $delta;
		} elseif ( $g == $maxRGB ) {
			$h = 2 + ( ( $b - $r ) / $delta );
		} else { // $b == $maxRGB
			$h = 4 + ( ( $r - $g ) / $delta );
		}

		// Multiple by 60 to get correct degrees.
		$h *= 60;

		return array( 'h' => $h, 's' => $s, 'v' => $v );
	}

	public function rgb_to_hsl( $r, $g, $b ) {
		// Convert to 0-1 ranges
		$r = $r / 255;
		$g = $g / 255;
		$b = $b / 255;

		// Get min, max, and delta
		$maxRGB = max( $r, $g, $b );
		$minRGB = min( $r, $g, $b );
		$delta = $maxRGB - $minRGB;

		// $l value as percentage
		$l = 100 * ($minRGB + $maxRGB) / 2;


		// If delta is 0, no saturation. Otherwise, calculate based on lightness.
		if ( $delta == 0 ) {
			$s = 0;
		} else {
			if ( $l <= 50 ) {
				$s = ( $maxRGB - $minRGB ) / ( $maxRGB + $minRGB );
			} else {
				$s = ( $maxRGB - $minRGB ) / ( 2.0 - $maxRGB - $minRGB );
			}
		}

		// $s as percentage
		$s = 100 * $s;

		// Calculate $h (hue) segment of 2D hexagonal plane based on maxRGB value
		if ( $r == $maxRGB ) {
			$h = ( $g - $b ) / $delta;
		} elseif ( $g == $maxRGB ) {
			$h = 2 + ( ( $b - $r ) / $delta );
		} else { // $b == $maxRGB
			$h = 4 + ( ( $r - $g ) / $delta );
		}

		// Multiple by 60 to get correct degrees.
		$h *= 60;

		if ( $h < 0 ) {
			$h += 360;
		}

		return array( 'h' => $h, 's' => $s, 'l' => $l );
	}

	public function hsv_to_rgb( $h, $s, $v ) {
		$h       = $h / 60;
		$s       = $s / 100;
		$v       = $v / 100;
		$h_floor = floor( $h );
		$chroma  = $v * $s;
		$m       = $v - $chroma;
		$x       = $chroma * ( 1 - abs( fmod( $h,  2 ) - 1 ) );

		switch( $h_floor ) {
			case 0:
				$r = $chroma;
				$g = $x;
				$b = 0;
				break;
			case 1:
				$r = $x;
				$g = $chroma;
				$b = 0;
				break;
			case 2:
				$r = 0;
				$g = $chroma;
				$b = $x;
				break;
			case 3:
				$r = 0;
				$g = $x;
				$b = $chroma;
				break;
			case 4:
				$r = $x;
				$g = 0;
				$b = $chroma;
				break;
			case 5:
			default:
				$r = $chroma;
				$g = 0;
				$b = $x;
				break;
		}

		return array( 'r' => 255 * ( $r + $m ), 'g' => 255 * ( $g + $m ), 'b' => 255 * ( $b + $m ) );
	}

	public function hsl_to_rgb( $h, $s, $l ) {
		$s       = $s / 100;
		$l       = $l / 100;

		// No saturation, just return values based on luminance.
		if ( $s == 0 ) {
			return array(
				'r' => $l * 255,
				'g' => $l * 255,
				'b' => $l * 255
			);
		}

		$temp1 = ( $l < 0.5 ) ? $l * ( 1.0 + $s ) : $l + $s - ( $l * $s );
		$temp2 = 2 * $l - $temp1;

		$h = ( $h / 360 );

		$tempR = $h + 0.333;
		$tempG = $h;
		$tempB = $h - 0.333;

		// force between 0 and 1
		if ( $tempR < 0 ) {
			$tempR += 1;
		}

		if ( $tempR > 1 ) {
			$tempR -= 1;
		}

		if ( $tempG < 0 ) {
			$tempG += 1;
		}

		if ( $tempG > 1 ) {
			$tempG -= 1;
		}

		if ( $tempB < 0 ) {
			$tempB += 1;
		}

		if ( $tempB > 1 ) {
			$tempB -= 1;
		}

		if ( ( 6 * $tempR ) < 1 ) {
			$r = $temp2 + ( $temp1 - $temp2 ) * 6 * $tempR;
		} elseif ( ( 2 * $tempR ) < 1 ) {
			$r = $temp1;
		} elseif ( ( 3 * $tempR ) < 2 ) {
			$r = $temp2 + ( $temp1 - $temp2 ) * ( 0.666 - $tempR ) * 6;
		} else {
			$r = $temp2;
		}

		if ( ( 6 * $tempG ) < 1 ) {
			$g = $temp2 + ( $temp1 - $temp2 ) * 6 * $tempG;
		} elseif ( ( 2 * $tempG ) < 1 ) {
			$g = $temp1;
		} elseif ( ( 3 * $tempG ) < 2 ) {
			$g = $temp2 + ( $temp1 - $temp2 ) * ( 0.666 - $tempG ) * 6;
		} else {
			$g = $temp2;
		}

		if ( ( 6 * $tempB ) < 1 ) {
			$b = $temp2 + ( $temp1 - $temp2 ) * 6 * $tempB;
		} elseif ( ( 2 * $tempB ) < 1 ) {
			$b = $temp1;
		} elseif ( ( 3 * $tempB ) < 2 ) {
			$b = $temp2 + ( $temp1 - $temp2 ) * ( 0.666 - $tempB ) * 6;
		} else {
			$b = $temp2;
		}

		return array( 'r' => $r * 255, 'g' => $g * 255, 'b' => $b * 255 );
	}

	public function make_variations_from_rgb( $r, $g, $b, $presets ) {
		$hsl = $this->rgb_to_hsl( $r, $g, $b );

		$variations = array();

		foreach( $presets as $preset ) {
			$new_s = $this->convert_from_preset( $preset['s'], $hsl['s'] / 100 );
			$new_l = $this->convert_from_preset( $preset['l'], $hsl['l'] / 100 );
			$variations[] = array( 'h' => $hsl['h'], 's' => $new_s, 'l' => $new_l );
		}

		array_walk( $variations, function( &$item ) {
			$item = $this->hsl_to_rgb( $item['h'], $item['s'] * 100, $item['l'] * 100 );
		});

		return $variations;
	}

	private function convert_from_preset( $val, $base_val ) {
		$x = $val + $base_val;

		if ( $x > 1 ) {
			return 1;
		}

		if ( $x < 0 ) {
			return 0;
		}

		return $x;
	}

	public function convert_rgb_to_hex( $r, $g, $b ) {
		return sprintf( "#%02x%02x%02x", $r, $g, $b );
	}

	/**
	 * Sanitize a color string to ensure it matches our required format.
	 *
	 * @param $color
	 *
	 * @return mixed|string
	 */
	public function sanitize_color_string( $color ) {
		// Remove the preceding # sign
		if ( strpos( $color, '#' ) !== false ) {
			$color = ltrim( $color, '#' );
		}

		// Make all strings 6-digit
		if ( strlen( $color ) === 3 ) {
			$color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
		}

		return $color;
	}

	public function convert_hex_to_rgb( $hex_code ) {
		list( $r, $g, $b ) = sscanf( $hex_code, '#%02x%02x%02x' );

		return array(
			'r' => $r,
			'g' => $g,
			'b' => $b,
		);
	}

	private function restrict_value_range( $val ) {
		if ( $val > 255 ) {
			return 255;
		}

		if ( $val < 0 ) {
			return 0;
		}

		return $val;
	}

	/**
	 * Modify a color by the specified amount.
	 *
	 * @param        $color_string
	 * @param int    $steps
	 * @param string $format
	 *
	 * @return string
	 */
	public function modify( $color_string, $steps, $format = 'hex' ) {
		$amount = $steps * self::STEP_VALUE;

		$color = $this->sanitize_color_string( $color_string );
		$num   = intval( $color, 16 );

		$r = ( $num >> 16 ) + $amount;
		$r = $this->restrict_value_range( $r );

		$g = ( $num & 0x0000FF ) + $amount;
		$g = $this->restrict_value_range( $g );

		$b = ( ( $num >> 8 ) & 0x00FF ) + $amount;
		$b = $this->restrict_value_range( $b );

		$dec_val = ( $g | ( $b << 8 ) | ( $r << 16 ) );
		$new_val = sprintf( '%06X', $dec_val );

		$modified = '#' . substr( $new_val, - 6 );

		if ( $format === 'rgb' ) {
			return $this->convert_hex_to_rgb( $modified );
		}

		return $modified;
	}

}
