<?php
/**
 * This file is licensed under MIT License.
 * 
 * Copyright (c) 2019 Ibrahim BinAlshikh
 * 
 * For more information on the license, please visit: 
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 * 
 */
namespace WebFiori\Database;

/**
 * A utility class which is used to validate date and time strings
 * for insert and update operations.
 *
 * @author Ibrahim
 * 
 * @version 1.0
 */
class DateTimeValidator {
    /**
     * Checks if date string represents a valid date.
     * 
     * @param string $date A string that represents the date in the format 
     * YYYY-MM-DD.
     * 
     * @return bool If the string represents a valid date, the method will return
     * true. Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public static function isValidDate(string $date) : bool {
        if (strlen($date) == 10) {
            $split = explode('-', $date);

            if (count($split) == 3) {
                $year = intval($split[0]);
                $month = intval($split[1]);
                $day = intval($split[2]);

                return $year > 1969 && $month > 0 && $month < 13 && $day > 0 && $day < 32;
            }
        }

        return false;
    }
    /**
     * Checks if a date-time string is valid or not.
     * 
     * @param string $dateTime A date string in the format 'YYYY-MM-DD HH:MM:SS'.
     * 
     * @return bool If the string represents correct date and time, the 
     * method will return true. False if it is not valid.
     * 
     * @since 1.0
     */
    public static function isValidDateTime(string $dateTime) : bool {
        $trimmed = trim($dateTime);

        if (strlen($trimmed) == 19) {
            $dateAndTime = explode(' ', $trimmed);

            if (count($dateAndTime) == 2) {
                return self::isValidDate($dateAndTime[0]) && self::isValidTime($dateAndTime[1]);
            }
        }

        return false;
    }
    /**
     * Checks if time string represents a valid time.
     * 
     * @param string $time A string that represents the time in the format 
     * HH:MM:SS. Note that the hours are in the 24 hours mode.
     * 
     * @return bool If the string represents a valid time, it will return
     * true. Other than that, the method will return false.
     * 
     * @since 1.0
     */
    public static function isValidTime(string $time) : bool {
        if (strlen($time) == 8) {
            $split = explode(':', $time);

            if (count($split) == 3) {
                $hours = intval($split[0]);
                $minutes = intval($split[1]);
                $sec = intval($split[2]);

                return $hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes < 60 && $sec >= 0 && $sec < 60;
            }
        }

        return false;
    }
}
