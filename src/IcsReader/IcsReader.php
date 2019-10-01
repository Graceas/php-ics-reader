<?php
/**
 * Created by PhpStorm.
 * User: gorelov
 * Date: 2019-10-01
 * Time: 16:41
 */

namespace IcsReader;

use IcsReader\Exception\IcsReaderContentIncorrectException;

/**
 * Class IcsReader
 * @package IcsReader
 */
class IcsReader
{
    /**
     * Creates the iCal-Object
     *
     * @param string $content        Content of the iCal-file
     * @param string $linesSeparator Lines separator, default PHP_EOL
     *
     * @return IcsResult
     *
     * @throws IcsReaderContentIncorrectException
     */
    public function parse($content, $linesSeparator = PHP_EOL)
    {
        $lines = explode($linesSeparator, $content);

        if (stristr($lines[0], 'BEGIN:VCALENDAR') === false) {
            throw new IcsReaderContentIncorrectException("Content is not correct");
        }

        $iCalResult = new IcsResult();

        $type = '';

        foreach ($lines as $line) {
            $line = trim($line);
            $add  = IcsHelper::keyValueFromString($line);

            if ($add === false) {
                $this->addCalendarComponentWithKeyAndValue($iCalResult, $type, false, $line);
                continue;
            }

            list($keyword, $value) = $add;

            switch ($line) {
                case "BEGIN:VTODO":
                    $iCalResult->setTodoCount($iCalResult->getTodoCount() + 1);
                    $type = "VTODO";
                    break;
                case "BEGIN:VEVENT":
                    $iCalResult->setEventCount($iCalResult->getEventCount() + 1);
                    $type = "VEVENT";
                    break;
                //all other special strings
                case "BEGIN:VCALENDAR":
                case "BEGIN:DAYLIGHT":
                case "BEGIN:VTIMEZONE":
                case "BEGIN:STANDARD":
                    $type = $value;
                    break;
                case "END:VTODO": // end special text - goto VCALENDAR key
                case "END:VEVENT":
                case "END:VCALENDAR":
                case "END:DAYLIGHT":
                case "END:VTIMEZONE":
                case "END:STANDARD":
                    $type = "VCALENDAR";
                    break;
                default:
                    $this->addCalendarComponentWithKeyAndValue($iCalResult, $type, $keyword, $value);
                    break;
            }
        }

        $this->processRecurrences($iCalResult);

        return $iCalResult;
    }

    /**
     * Add to $iCalResult one value and key.
     *
     * @param IcsResult $iCalResult Reference object
     * @param string    $component  This could be VTODO, VEVENT, VCALENDAR, etc
     * @param string    $keyword    The keyword, for example DTSTART
     * @param string    $value      The value, for example 20110105T090000Z
     *
     * @return void
     */
    private function addCalendarComponentWithKeyAndValue(&$iCalResult, $component, $keyword, $value)
    {
        if (strstr($keyword, ';')) {
            $keyword = substr($keyword, 0, strpos($keyword, ";"));
        }

        $calendar = $iCalResult->getCalendar();

        if ($keyword == false) {
            $keyword = $iCalResult->getLastKeyword();
            switch ($component) {
                case 'VEVENT':
                    $value = $calendar[$component][$iCalResult->getEventCount() - 1][$keyword] . $value;
                    break;
                case 'VTODO':
                    $value = $calendar[$component][$iCalResult->getEventCount() - 1][$keyword] . $value;
                    break;
            }
        }

        if (stristr($keyword, "DTSTART") or stristr($keyword, "DTEND")) {
            $keyword = explode(";", $keyword);
            $keyword = $keyword[0];
        }

        switch ($component) {
            case "VTODO":
                $calendar[$component][$iCalResult->getEventCount() - 1][$keyword] = $value;
                break;
            case "VEVENT":
                $calendar[$component][$iCalResult->getEventCount() - 1][$keyword] = $value;
                break;
            default:
                $calendar[$component][$keyword] = $value;
                break;
        }

        $iCalResult->setCalendar($calendar);
        $iCalResult->setLastKeyword($keyword);
    }

    /**
     * Processes recurrences
     *
     * @param IcsResult $iCalResult $iCalResult Reference object
     *
     * @return void
     */
    private function processRecurrences(&$iCalResult)
    {
        $events     = $iCalResult->getEvents();
        $eventsCopy = $iCalResult->getEvents();

        if (!$iCalResult->hasEvents()) {
            return;
        }

        foreach ($eventsCopy as $anEvent) {
            if (isset($anEvent['RRULE']) && $anEvent['RRULE'] != '') {
                // Recurring event, parse RRULE and add appropriate duplicate events
                $rules = array();
                $ruleStrings = explode(';', $anEvent['RRULE']);
                foreach ($ruleStrings as $s) {
                    list($k, $v) = explode('=', $s);
                    $rules[$k] = $v;
                }
                // Get Start timestamp
                $startTimestamp = IcsHelper::iCalDateToUnixTimestamp($anEvent['DTSTART']);
                $endTimestamp = IcsHelper::iCalDateToUnixTimestamp($anEvent['DTEND']);
                $eventTimestampOffset = $endTimestamp - $startTimestamp;
                // Get Interval
                $interval = (isset($rules['INTERVAL']) && $rules['INTERVAL'] != '') ? $rules['INTERVAL'] : 1;
                // Get Until
                $until = IcsHelper::iCalDateToUnixTimestamp(@$rules['UNTIL']);
                // Decide how often to add events and do so
                switch ($rules['FREQ']) {
                    case 'DAILY':
                        // Simply add a new event each interval of days until UNTIL is reached
                        $offset = " + $interval day";
                        $recurringTimestamp = strtotime($offset, $startTimestamp);
                        while ($recurringTimestamp <= $until) {
                            // Add event
                            $anEvent['DTSTART'] = date('Ymd\THis', $recurringTimestamp);
                            $anEvent['DTEND'] = date('Ymd\THis', $recurringTimestamp + $eventTimestampOffset);
                            $events[] = $anEvent;
                            // Move forward
                            $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                        }
                        break;
                    case 'WEEKLY':
                        // Create offset
                        $offset = " + $interval week";
                        // Build list of days of week to add events
                        $weekdays = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
                        $bydays = (isset($rules['BYDAY']) && $rules['BYDAY'] != '') ? explode(', ', $rules['BYDAY']) : array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
                        // Get timestamp of first day of start week
                        $weekRecurringTimestamp = (date('w', $startTimestamp) == 0) ? $startTimestamp : strtotime('last Sunday ' . date('H:i:s', $startTimestamp), $startTimestamp);
                        // Step through weeks
                        while ($weekRecurringTimestamp <= $until) {
                            // Add events for bydays
                            $dayRecurringTimestamp = $weekRecurringTimestamp;
                            foreach ($weekdays as $day) {
                                // Check if day should be added
                                if (in_array($day, $bydays) && $dayRecurringTimestamp > $startTimestamp && $dayRecurringTimestamp <= $until) {
                                    // Add event to day
                                    $anEvent['DTSTART'] = date('Ymd\THis', $dayRecurringTimestamp);
                                    $anEvent['DTEND'] = date('Ymd\THis', $dayRecurringTimestamp + $eventTimestampOffset);
                                    $events[] = $anEvent;
                                }
                                // Move forward a day
                                $dayRecurringTimestamp = strtotime(' + 1 day', $dayRecurringTimestamp);
                            }
                            // Move forward $interaval weeks
                            $weekRecurringTimestamp = strtotime($offset, $weekRecurringTimestamp);
                        }
                        break;
                    case 'MONTHLY':
                        // Create offset
                        $offset = " + $interval month";
                        $recurringTimestamp = strtotime($offset, $startTimestamp);
                        if (isset($rules['BYMONTHDAY']) && $rules['BYMONTHDAY'] != '') {
                            // Deal with BYMONTHDAY
                            while ($recurringTimestamp <= $until) {
                                // Add event
                                $anEvent['DTSTART'] = date('Ym' . sprintf('%02d', $rules['BYMONTHDAY']) . '\THis', $recurringTimestamp);
                                $anEvent['DTEND'] = date('Ymd\THis', IcsHelper::iCalDateToUnixTimestamp($anEvent['DTSTART']) + $eventTimestampOffset);
                                $events[] = $anEvent;
                                // Move forward
                                $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                            }
                        } elseif (isset($rules['BYDAY']) && $rules['BYDAY'] != '') {
                            $startTime = date('His', $startTimestamp);
                            // Deal with BYDAY
                            $dayNumber = substr($rules['BYDAY'], 0, 1);
                            $weekDay = substr($rules['BYDAY'], 1);
                            $dayCardinals = array(1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth');
                            $weekdays = array('SU' => 'sunday', 'MO' => 'monday', 'TU' => 'tuesday', 'WE' => 'wednesday', 'TH' => 'thursday', 'FR' => 'friday', 'SA' => 'saturday');
                            while ($recurringTimestamp <= $until) {
                                $eventStartDesc = "{$dayCardinals[$dayNumber]} {$weekdays[$weekDay]} of " . date('F', $recurringTimestamp) . " " . date('Y', $recurringTimestamp) . " " . date('H:i:s', $recurringTimestamp);
                                $eventStartTimestamp = strtotime($eventStartDesc);
                                if ($eventStartTimestamp > $startTimestamp && $eventStartTimestamp < $until) {
                                    $anEvent['DTSTART'] = date('Ymd\T', $eventStartTimestamp) . $startTime;
                                    $anEvent['DTEND'] = date('Ymd\THis', IcsHelper::iCalDateToUnixTimestamp($anEvent['DTSTART']) + $eventTimestampOffset);
                                    $events[] = $anEvent;
                                }
                                // Move forward
                                $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                            }
                        }
                        break;
                    case 'YEARLY':
                        // Create offset
                        $offset = " + $interval year";
                        $recurringTimestamp = strtotime($offset, $startTimestamp);
                        $monthNames = array(1 => "January", 2 => "Februrary", 3 => "March", 4 => "April", 5 => "May", 6 => "June", 7 => "July", 8 => "August", 9 => "September", 10 => "October", 11 => "November", 12 => "December");
                        // HACK: Exchange doesn't set a correct UNTIL for yearly events, so just go 2 years out
                        $until = strtotime(' + 2 year', $startTimestamp);
                        // Check if BYDAY rule exists
                        $startTime = '000000';
                        if (isset($rules['BYDAY']) && $rules['BYDAY'] != '') {
                            $startTime = date('His', $startTimestamp);
                            // Deal with BYDAY
                            $dayNumber = substr($rules['BYDAY'], 0, 1);
                            $monthDay = substr($rules['BYDAY'], 1);
                            $dayCardinals = array(1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth');
                            $weekdays = array('SU' => 'sunday', 'MO' => 'monday', 'TU' => 'tuesday', 'WE' => 'wednesday', 'TH' => 'thursday', 'FR' => 'friday', 'SA' => 'saturday');
                            while ($recurringTimestamp <= $until) {
                                $eventStartDesc = "{$dayCardinals[$dayNumber]} {$weekdays[$monthDay]} of {$monthNames[$rules['BYMONTH']]} " . date('Y', $recurringTimestamp) . " " . date('H:i:s', $recurringTimestamp);
                                $eventStartTimestamp = strtotime($eventStartDesc);
                                if ($eventStartTimestamp > $startTimestamp && $eventStartTimestamp < $until) {
                                    $anEvent['DTSTART'] = date('Ymd\T', $eventStartTimestamp) . $startTime;
                                    $anEvent['DTEND'] = date('Ymd\THis', IcsHelper::iCalDateToUnixTimestamp($anEvent['DTSTART']) + $eventTimestampOffset);
                                    $events[] = $anEvent;
                                }
                                // Move forward
                                $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                            }
                        } else {
                            $day = date('d', $startTimestamp);
                            // Step through years adding specific month dates
                            while ($recurringTimestamp <= $until) {
                                $eventStartDesc = "$day {$monthNames[$rules['BYMONTH']]} " . date('Y', $recurringTimestamp) . " " . date('H:i:s', $recurringTimestamp);
                                $eventStartTimestamp = strtotime($eventStartDesc);
                                if ($eventStartTimestamp > $startTimestamp && $eventStartTimestamp < $until) {
                                    $anEvent['DTSTART'] = date('Ymd\T', $eventStartTimestamp) . $startTime;
                                    $anEvent['DTEND'] = date('Ymd\THis', IcsHelper::iCalDateToUnixTimestamp($anEvent['DTSTART']) + $eventTimestampOffset);
                                    $events[] = $anEvent;
                                }
                                // Move forward
                                $recurringTimestamp = strtotime($offset, $recurringTimestamp);
                            }
                        }
                        break;
                }
            }
        }

        // update events
        $calendar = $iCalResult->getCalendar();
        $calendar['VEVENT'] = $events;
        $iCalResult->setCalendar($calendar);
    }
}
