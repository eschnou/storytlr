<?php class Stuffpress_Exif {

	// TODO !! Quick and dirty rip from wordpress !! to be changed !
	public static function exif_gpsconvert($geo) {
	    @list( $degree, $minute, $second ) = $geo;
	    $float = self::exif_frac2dec($degree)  + (self::exif_frac2dec($minute)/60) + (self::exif_frac2dec($second)/3600);
	    
	    return is_float($float) ? $float : 999;
	}
	
	public static function exif_frac2dec($str) {
	    @list( $n, $d ) = explode( '/', $str );
	    if ( !empty($d) )
	        return $n / $d;
	    return $str;
	}
	
}