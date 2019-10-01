<?php
/**
 * Created by PhpStorm.
 * User: gorelov
 * Date: 2019-10-01
 * Time: 16:53
 */

namespace IcsReader;

/**
 * Class IcsResult
 * @package IcsReader
 */
class IcsResult
{
    /**
     * @var int How many ToDos are in this ical
     */
    private $todoCount = 0;

    /**
     * @var int How many events are in this ical?
     */
    private $eventCount = 0;

    /**
     * @var array The parsed calendar
     */
    private $calendar;

    /**
     * @var string Last keywords
     */
    private $lastKeyword;

    /**
     * @return int
     */
    public function getTodoCount()
    {
        return $this->todoCount;
    }

    /**
     * @param int $todoCount
     */
    public function setTodoCount($todoCount)
    {
        $this->todoCount = $todoCount;
    }

    /**
     * @return int
     */
    public function getEventCount()
    {
        return $this->eventCount;
    }

    /**
     * @param int $eventCount
     */
    public function setEventCount($eventCount)
    {
        $this->eventCount = $eventCount;
    }

    /**
     * @return array
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Returns an array of arrays with all events. Every event is an associative
     * array and each property is an element it.
     *
     * @return array
     */
    public function getEvents()
    {
        return (isset($this->calendar['VEVENT'])) ? $this->calendar['VEVENT'] : array();
    }

    /**
     * Returns a boolean value whether the current calendar has events or not
     *
     * @return boolean
     */
    public function hasEvents()
    {
        return (count($this->getEvents()) > 0 ? true : false);
    }

    /**
     * Sort event with order.
     *
     * @param int $sortOrder Either SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING
     *
     * @return array
     */
    public function sortEventsWithOrder($sortOrder = SORT_ASC)
    {
        $events = $this->getEvents();

        if (count($events) == 0) {
            return array();
        }

        $extendedEvents = array();

        // loop through all events by adding two new elements
        foreach ($events as $anEvent) {
            if (!array_key_exists('UNIX_TIMESTAMP', $anEvent)) {
                $anEvent['UNIX_TIMESTAMP'] = IcsHelper::iCalDateToUnixTimestamp($anEvent['DTSTART']);
            }
            if (!array_key_exists('REAL_DATETIME', $anEvent)) {
                $anEvent['REAL_DATETIME'] = date("d.m.Y", $anEvent['UNIX_TIMESTAMP']);
            }

            $extendedEvents[] = $anEvent;
        }

        foreach ($extendedEvents as $key => $value) {
            $timestamp[$key] = $value['UNIX_TIMESTAMP'];
        }

        array_multisort($timestamp, $sortOrder, $extendedEvents);

        return $extendedEvents;
    }


    /**
     * Returns false when the current calendar has no events in range, else the events.
     *
     * Note that this function makes use of a UNIX timestamp. This might be a
     * problem on January the 29th, 2038.
     * See http://en.wikipedia.org/wiki/Unix_time#Representing_the_number
     *
     * @param boolean|\DateTime $rangeStart DateTime or false (current date will be used)
     * @param boolean|\DateTime $rangeEnd   DateTime or false (2038/01/18 will be used)
     *
     * @return array
     * @throws \Exception
     */
    public function getEventsFromRange($rangeStart = false, $rangeEnd = false)
    {
        $events = $this->sortEventsWithOrder(SORT_ASC);

        if (!$events) {
            return array();
        }

        $extendedEvents = array();

        $rangeStart = (!$rangeStart) ? new \DateTime() : $rangeStart;
        $rangeEnd   = (!$rangeEnd) ? new \DateTime('2038/01/18') : $rangeEnd;

        $rangeStart = $rangeStart->format('U');
        $rangeEnd   = $rangeEnd->format('U');

        // loop through all events by adding two new elements
        foreach ($events as $anEvent) {
            $timestamp = IcsHelper::iCalDateToUnixTimestamp($anEvent['DTSTART']);
            if ($timestamp >= $rangeStart && $timestamp <= $rangeEnd) {
                $extendedEvents[] = $anEvent;
            }
        }
        return $extendedEvents;
    }

    /**
     * @param array $calendar
     */
    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;
    }

    /**
     * @return string
     */
    public function getLastKeyword()
    {
        return $this->lastKeyword;
    }

    /**
     * @param string $lastKeyword
     */
    public function setLastKeyword($lastKeyword)
    {
        $this->lastKeyword = $lastKeyword;
    }
}
