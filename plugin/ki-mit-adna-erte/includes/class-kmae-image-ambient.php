<?php
/**
 * A kép „ambient” színe (a böngészés v2 kártya háttere) és hangulata.
 *
 * Tisztán számolós osztály: nincs benne WordPress- vagy képkönyvtár-hívás, csak
 * egy kis (kb. 32 px széles) pixeltömbből dolgozik, ezért WordPress nélkül is
 * futtatható és tesztelhető (lásd tests/test-image-ambient.php). A pixeleket
 * a KMAE_Image_Cards olvassa ki a feltöltött képből.
 *
 * @package KMAE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KMAE_Image_Ambient {

	/** Szín-vödör: csatornánként 4 szint (0–63, 64–127, …) — a színátmenetes ég nem aprózódik szét. */
	const BUCKET_SHIFT = 6;

	/** A kép közepének súlya (a szélé ~1,1): a háttér a kép szélének színe. */
	const WEIGHT_FLOOR = 0.1;

	/** Színesség-torzítás a vödör kiválasztásánál (lásd analyze()). */
	const CHROMA_FLOOR = 0.2;
	const CHROMA_GAIN  = 3.0;

	/* Háttérnek illesztés: sötét–közepes világosság, nem neon telítettség. */
	const LIGHTNESS_MIN = 0.14;
	const LIGHTNESS_MAX = 0.38;
	const SATURATION_MAX = 0.75;

	/**
	 * @param int       $width  Pixelrács szélessége.
	 * @param int       $height Pixelrács magassága.
	 * @param int[]     $px     Lapos RGB-lista soronként: r,g,b,r,g,b,… (0–255).
	 * @return array{ambient:string,raw:string,tone:string}|null
	 *   ambient = háttérnek igazított szín, raw = a szél domináns színe igazítás nélkül,
	 *   tone = warm (a kép átlagában a piros ≥ kék) | cool.
	 */
	public static function analyze( $width, $height, array $px ) {
		$width  = (int) $width;
		$height = (int) $height;
		if ( $width < 1 || $height < 1 || count( $px ) < $width * $height * 3 ) {
			return null;
		}

		$buckets = array();
		$sum_r   = 0;
		$sum_b   = 0;
		$i       = 0;
		for ( $y = 0; $y < $height; $y++ ) {
			$ny = ( $y + 0.5 ) / $height * 2 - 1;
			for ( $x = 0; $x < $width; $x++ ) {
				$nx   = ( $x + 0.5 ) / $width * 2 - 1;
				$edge = max( abs( $nx ), abs( $ny ) );
				$wt   = self::WEIGHT_FLOOR + $edge * $edge;
				$r    = (int) $px[ $i ];
				$g    = (int) $px[ $i + 1 ];
				$b    = (int) $px[ $i + 2 ];
				$i   += 3;

				$key = ( ( $r >> self::BUCKET_SHIFT ) << 4 ) | ( ( $g >> self::BUCKET_SHIFT ) << 2 ) | ( $b >> self::BUCKET_SHIFT );
				if ( ! isset( $buckets[ $key ] ) ) {
					$buckets[ $key ] = array( 0.0, 0.0, 0.0, 0.0 );
				}
				$buckets[ $key ][0] += $wt;
				$buckets[ $key ][1] += $r * $wt;
				$buckets[ $key ][2] += $g * $wt;
				$buckets[ $key ][3] += $b * $wt;
				$sum_r              += $r;
				$sum_b              += $b;
			}
		}

		/* A vödör pontszáma: a szél felé súlyozott terület × (0,2 + 3 × színesség). A színesség
		   nélkül a sötét/zajos kép szürke árnyékai (és az ég + út fehéres átlaga) mindig
		   nyernének, és a háttér nem hordozná a kép színét. Döntetlennél a legkisebb
		   vödörkulcs nyer (ksort + szigorú „nagyobb”), így a Python-mása ugyanazt adja. */
		ksort( $buckets );
		$best       = null;
		$best_score = -1.0;
		foreach ( $buckets as $bucket ) {
			$mr     = $bucket[1] / $bucket[0];
			$mg     = $bucket[2] / $bucket[0];
			$mb     = $bucket[3] / $bucket[0];
			$chroma = ( max( $mr, $mg, $mb ) - min( $mr, $mg, $mb ) ) / 255;
			$score  = $bucket[0] * ( self::CHROMA_FLOOR + self::CHROMA_GAIN * $chroma );
			if ( $score > $best_score ) {
				$best_score = $score;
				$best       = $bucket;
			}
		}

		$r = $best[1] / $best[0];
		$g = $best[2] / $best[0];
		$b = $best[3] / $best[0];

		list( $h, $s, $l ) = self::rgb_to_hsl( $r, $g, $b );
		$l                 = min( max( $l, self::LIGHTNESS_MIN ), self::LIGHTNESS_MAX );
		$s                 = min( $s, self::SATURATION_MAX );
		list( $ar, $ag, $ab ) = self::hsl_to_rgb( $h, $s, $l );

		return array(
			'ambient' => self::hex( $ar, $ag, $ab ),
			'raw'     => self::hex( $r, $g, $b ),
			'tone'    => $sum_r >= $sum_b ? 'warm' : 'cool',
		);
	}

	/** @return string #rrggbb (a csatornák 0–255 közötti lebegőpontosak is lehetnek). */
	private static function hex( $r, $g, $b ) {
		return sprintf( '#%02x%02x%02x', self::byte( $r ), self::byte( $g ), self::byte( $b ) );
	}

	/** Kerekítés „fél felfelé”, hogy a Python-mása ugyanazt adja (annak a round()-ja páros felé kerekít). */
	private static function byte( $v ) {
		return (int) min( 255, max( 0, floor( $v + 0.5 ) ) );
	}

	/** @return float[] [h 0–1, s 0–1, l 0–1] */
	private static function rgb_to_hsl( $r, $g, $b ) {
		$r  /= 255.0;
		$g  /= 255.0;
		$b  /= 255.0;
		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );
		$l   = ( $max + $min ) / 2;
		$d   = $max - $min;
		if ( $d <= 0.0 ) {
			return array( 0.0, 0.0, $l );
		}
		$s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
		if ( $max === $r ) {
			$h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 );
		} elseif ( $max === $g ) {
			$h = ( $b - $r ) / $d + 2;
		} else {
			$h = ( $r - $g ) / $d + 4;
		}
		return array( $h / 6, $s, $l );
	}

	/** @return float[] [r, g, b] 0–255. */
	private static function hsl_to_rgb( $h, $s, $l ) {
		if ( $s <= 0.0 ) {
			return array( $l * 255, $l * 255, $l * 255 );
		}
		$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
		$p = 2 * $l - $q;
		return array(
			self::hue_to_channel( $p, $q, $h + 1 / 3 ) * 255,
			self::hue_to_channel( $p, $q, $h ) * 255,
			self::hue_to_channel( $p, $q, $h - 1 / 3 ) * 255,
		);
	}

	private static function hue_to_channel( $p, $q, $t ) {
		if ( $t < 0 ) {
			$t += 1;
		}
		if ( $t > 1 ) {
			$t -= 1;
		}
		if ( $t < 1 / 6 ) {
			return $p + ( $q - $p ) * 6 * $t;
		}
		if ( $t < 1 / 2 ) {
			return $q;
		}
		if ( $t < 2 / 3 ) {
			return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6;
		}
		return $p;
	}
}
