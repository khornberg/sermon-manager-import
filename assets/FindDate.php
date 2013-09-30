<?php
/**
 * Find Date in a String
 *
 * @author   Etienne Tremel
 * @internal  Modified by Kyle Hornberg
 * @license  http://creativecommons.org/licenses/by/3.0/ CC by 3.0
 * @link     http://www.etiennetremel.net
 *
 * @param string	find_date( ' some text 01/01/2012 some text' ) or find_date( ' some text October 5th 86 some text' )
 * @return mixed	false if no date found else array: array( 'day' => 01, 'month' => 01, 'year' => 2012 )
 */

class FindDate {
	//Define month name:
	public $month_names = array( 
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

	// public $month_number         = "";
	public $month                = "";
	public $matches_year         = "";
	public $year                 = "";
	public $matches_month_number = "";
	public $matches_month_word   = "";
	public $matches_day_number   = "";
	public $day                  = "";
	public $meridiem             = "";

	public $two_digit_year_split = 50;
	public $format               = ""; 

	public function find( $string ) {
		$this->month                = "";
		$this->year                 = "";
		$this->day                  = "";
		$this->meridiem             = "";

		switch ($this->format) {
	            case 'YYYYMMDD':
	            case 'YYYYMD':
	            	//Match dates: 20130311 or 13-12-31, etc
	                preg_match( '/([0-9]{2,4})[\.\-\_\/\\ ]?(([0-1]?[0-9])|([a-zA-Z]+))[\.\-\_\/\\ ]?([0-9]?[0-9])[\.\-\/\\ ]?((am|AM)|(a|A)|(m|M)orning|(pm|PM)|(p|P)|(e|E)vening)?/', $string, $matches );
	                if ( $matches ) {
						if ( isset($matches[5]) )
							$this->day = $matches[5];

						if ( isset($matches[2]) )
							$this->month = $matches[2];

						if ( isset($matches[1]) )
							$this->year = $matches[1];

						if ( isset($matches[6]) )
							$this->meridiem = $matches[6];
					}
	                break;
	            case 'DDMMYYYY':
	            case 'DMYYYY':
	            	//Match dates: 01/01/2012 or 30-12-11 or 1 2 1985
					preg_match( '/([0-9]?[0-9])[\.\-\/\\ ]?(([0-1]?[0-9])|([a-zA-Z]*))[\.\-\/\\ ]?([0-9]{2,4})[\.\-\/\\ ]?((a|A)|(am|AM)|(m|M)orning|(p|P)|(pm|PM)|(e|E)vening)?/', $string, $matches );
					if ( $matches ) {
						if ( isset($matches[1]) )
							$this->day = $matches[1];

						if ( isset($matches[2]) )
							$this->month = $matches[2];

						if ( isset($matches[5]) )
							$this->year = $matches[5];

						if ( isset($matches[6]) )
							$this->meridiem = $matches[6];
					}
	  				break;
	  			case 'MMDDDYYYY':
	            case 'MDYYYY':
	            	break;
	        }

		//Match month name:
		preg_match( '/(' . implode( '|', $this->month_names ) . ')/i', $string, $matches_month_word );
		if ( $matches_month_word ) {
			if ( $matches_month_word[1] )
				$this->month = array_search( strtolower( $matches_month_word[1] ),  $this->month_names ) + 1;
		}

		//Match 5th 1st day:
		preg_match( '/([0-9]?[0-9])(st|nd|th)/', $string, $matches_day );
		if ( $matches_day ) {
			if ( $matches_day[1] )
				$this->day = $matches_day[1];
		}

		//Match Year if not already setted:
		if ( empty( $this->year ) ) {
			preg_match( '/[0-9]{4}/', $string, $matches_year );
			if ( $matches_year[0] )
				$this->year = $matches_year[0];
		}
		if ( ! empty ( $this->day ) && ! empty ( $this->month ) && empty( $this->year ) ) {
			preg_match( '/[0-9]{2}/', $string, $matches_year );
			if ( $matches_year[0] )
				$this->year = $matches_year[0];
		}

		//Leading 0
		if ( 1 == strlen( $this->day ) ) 
			$this->day = '0' . $this->day;

		//Leading 0
		if ( 1 == strlen( $this->month ) )
			$this->month = '0' . $this->month;

		//Check year:
		if ( 2 == strlen( $this->year ) && $this->year >= $this->two_digit_year_split )
			$this->year = '19' . $this->year;
		else if ( 2 == strlen( $this->year ) && $this->year < $this->two_digit_year_split )
			$this->year = '20' . $this->year;

		$date = array(
			'year' 	=> $this->year,
			'month' => $this->month,
			'day' 	=> $this->day,
			'meridiem' => $this->meridiem
		);

		//Return false if nothing found:
		if ( empty( $this->year ) && empty( $this->month ) && empty( $this->day ) && empty( $this->meridiem ) )
			return false;
		//Return false if not a valid date
		// else if ( checkdate( $this->year, $this->month, $this->day ) )
		// 	return false;
		else
			return $date;
	}
}
