<?php
/**
 * Find Date in a String
 *
 * @author   Etienne Tremel
 * @license  http://creativecommons.org/licenses/by/3.0/ CC by 3.0
 * @link     http://www.etiennetremel.net
 * @modified Kyle Hornberg
 *
 * @param string	find_date( ' some text 01/01/2012 some text' ) or find_date( ' some text October 5th 86 some text' )
 * @return mixed	false if no date found else array: array( 'day' => 01, 'month' => 01, 'year' => 2012 )
 */
function find_date( $string ) {

	//Define month name:
	$month_names = array( 
		"january",
		"february",
		"march",
		"april",
		"may",
		"june",
		"july",
		"august",
		"september",
		"october",
		"november",
		"december"
	);

	$month_number=$month=$matches_year=$year=$matches_month_number=$matches_month_word=$matches_day_number=$day=$meridiem="";
	
	$format = 'YYYYMMDD'; //get_option('date_format');

	switch ($format) {
            case 'YYYYMMDD':
            	//Match dates: 20130311 or 13-12-31, etc
                preg_match( '/([0-9]{2,4})[\.\-\_\/\\ ]?([0-1]?[0-9])[\.\-\_\/\\ ]?([0-9]?[0-9])((a|A)|(am|AM)|morning|(p|P)|(pm|PM)|evening)?/', $string, $matches );
                if ( $matches ) {
					if ( $matches[1] )
						$day = $matches[3];

					if ( $matches[2] )
						$month = $matches[2];

					if ( $matches[3] )
						$year = $matches[1];

					if ( $matches[4] )
						$meridiem = $matches[4];
				}
                break;
            case 'DDMMYYYY':
            case 'DMYYYY':
            	//Match dates: 01/01/2012 or 30-12-11 or 1 2 1985
				preg_match( '/([0-9]?[0-9])[\.\-\/\\ ]?([0-1]?[0-9])[\.\-\/\\ ]?([0-9]{2,4})((a|A)|(am|AM)|morning|(p|P)|(pm|PM)|evening)?/', $string, $matches );
				if ( $matches ) {
					if ( $matches[1] )
						$day = $matches[1];

					if ( $matches[2] )
						$month = $matches[2];

					if ( $matches[3] )
						$year = $matches[3];

					if ( $matches[4] )
						$meridiem = $matches[4];
				}
  				break;
  			case 'MMDDDYYYY':
            case 'MDYYYY':
            	break;
        }
	

	//Match month name:
	preg_match( '/(' . implode( '|', $month_names ) . ')/i', $string, $matches_month_word );

	if ( $matches_month_word ) {
		if ( $matches_month_word[1] )
			$month = array_search( strtolower( $matches_month_word[1] ),  $month_names ) + 1;
	}

	//Match 5th 1st day:
	preg_match( '/([0-9]?[0-9])(st|nd|th)/', $string, $matches_day );
	if ( $matches_day ) {
		if ( $matches_day[1] )
			$day = $matches_day[1];
	}

	//Match Year if not already setted:
	if ( empty( $year ) ) {
		preg_match( '/[0-9]{4}/', $string, $matches_year );
		if ( $matches_year[0] )
			$year = $matches_year[0];
	}
	if ( ! empty ( $day ) && ! empty ( $month ) && empty( $year ) ) {
		preg_match( '/[0-9]{2}/', $string, $matches_year );
		if ( $matches_year[0] )
			$year = $matches_year[0];
	}

	//Leading 0
	if ( 1 == strlen( $day ) )
		$day = '0' . $day;

	//Leading 0
	if ( 1 == strlen( $month ) )
		$month = '0' . $month;

	//Check year:
	if ( 2 == strlen( $year ) && $year > 50 )
		$year = '19' . $year;
	else if ( 2 == strlen( $year ) && $year < 50 )
		$year = '20' . $year;

	$date = array(
		'year' 	=> $year,
		'month' => $month,
		'day' 	=> $day,
		'meridiem' => $meridiem
	);

	//Return false if nothing found:
	if ( empty( $year ) && empty( $month ) && empty( $day ) && empty( $meridiem ) )
		return false;
	//Return false if not a valid date
	else if ( checkdate( $year, $month, $day ) )
		return false;
	else
		return $date;
}
?>