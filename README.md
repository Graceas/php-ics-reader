IcsReader
=========

This class for parse iCal-file (*.ics).

Installation
============

Through composer:

    "require": {
        ...
        "graceas/php-ics-reader": "dev-master"
        ...
    }

Usage
=====

Code:

    <?php
    include 'vendor/autoload.php';
    
    use IcsReader\IcsReader;
    
    $icsContent = <<<EOF
    BEGIN:VCALENDAR
    PRODID:-//Google Inc//Google Calendar 70.9054//EN
    VERSION:2.0
    CALSCALE:GREGORIAN
    METHOD:PUBLISH
    X-WR-CALNAME:TestCalendar
    X-WR-TIMEZONE:Europe/Berlin
    X-WR-CALDESC:Test Google Calendar
    BEGIN:VEVENT
    DTSTART:20110105T090000Z
    DTEND:20110107T173000Z
    DTSTAMP:20110121T195741Z
    UID:a4@google.com
    CREATED:20110121T195616Z
    DESCRIPTION:This is a short description\nwith a new line. Some "special" 's
     igns' may be <interesting>\, too.
    LAST-MODIFIED:20110121T195729Z
    LOCATION:Kansas
    SEQUENCE:2
    STATUS:CONFIRMED
    SUMMARY:My Holidays
    TRANSP:TRANSPARENT
    END:VEVENT
    BEGIN:VEVENT
    DTSTART;VALUE=DATE:20110112
    DTEND;VALUE=DATE:20110116
    DTSTAMP:20110121T195741Z
    UID:a4@google.com
    CREATED:20110119T142901Z
    DESCRIPTION:Project xyz Review Meeting Minutes\n
     Agenda\n1. Review of project version 1.0 requirements.\n2.
     Definition
     of project processes.\n3. Review of project schedule.\n
     Participants: John Smith, Jane Doe, Jim Dandy\n-It was
      decided that the requirements need to be signed off by
      product marketing.\n-Project processes were accepted.\n
     -Project schedule needs to account for scheduled holidays
      and employee vacation time. Check with HR for specific
      dates.\n-New schedule will be distributed by Friday.\n-
     Next weeks meeting is cancelled. No meeting until 3/23.
    LAST-MODIFIED:20110119T152216Z
    LOCATION:
    SEQUENCE:2
    STATUS:CONFIRMED
    SUMMARY:test 11
    TRANSP:TRANSPARENT
    END:VEVENT
    BEGIN:VEVENT
    DTSTART;VALUE=DATE:20110119
    DTEND;VALUE=DATE:20110120
    DTSTAMP:20110121T195741Z
    UID:a3@google.com
    CREATED:20110119T141923Z
    DESCRIPTION:
    LAST-MODIFIED:20110119T141923Z
    LOCATION:
    SEQUENCE:0
    STATUS:CONFIRMED
    SUMMARY:test 6
    TRANSP:TRANSPARENT
    END:VEVENT
    BEGIN:VEVENT
    DTSTART;VALUE=DATE:20110119
    DTEND;VALUE=DATE:20110120
    DTSTAMP:20110121T195741Z
    UID:a2@google.com
    CREATED:20110119T141913Z
    DESCRIPTION:
    LAST-MODIFIED:20110119T141913Z
    LOCATION:
    SEQUENCE:0
    STATUS:CONFIRMED
    SUMMARY:test 4
    TRANSP:TRANSPARENT
    END:VEVENT
    BEGIN:VEVENT
    DTSTART;VALUE=DATE:20400201
    DTEND;VALUE=DATE:20400202
    DTSTAMP:20400101T195741Z
    UID:a1@google.com
    CREATED:20400101T141901Z
    DESCRIPTION:
    LAST-MODIFIED:20400101T141901Z
    LOCATION:
    SEQUENCE:0
    STATUS:CONFIRMED
    SUMMARY:Year 2038 problem test
    TRANSP:TRANSPARENT
    END:VEVENT
    BEGIN:VEVENT
    DTSTART;VALUE=DATE:19410512
    DTEND;VALUE=DATE:19410512
    DTSTAMP:19410512T195741Z
    UID:a6@google.com
    CREATED:20400101T141901Z
    DESCRIPTION:
    LAST-MODIFIED:20400101T141901Z
    LOCATION:
    SEQUENCE:0
    STATUS:CONFIRMED
    SUMMARY:Before 1970-Test: John Doe invents the Z3, the first digital Computer
    TRANSP:TRANSPARENT
    END:VEVENT
    END:VCALENDAR
    EOF;
    
    $reader = new IcsReader();
    $ics    = $reader->parse($icsContent);
    
    print_r($ics->getCalendar());
    print_r($ics->getEvents());

Example output:
    
    Array
    (
        [0] => Array
            (
                [DTSTART] => 20110105T090000Z
                [DTEND] => 20110107T173000Z
                [DTSTAMP] => 20110121T195741Z
                [UID] => a4@google.com
                [CREATED] => 20110121T195616Z
                [DESCRIPTION] => This is a short descriptionwith a new line. Some "special" 'signs' may be <interesting>\, too.
                [LAST-MODIFIED] => 20110121T195729Z
                [LOCATION] => Kansas
                [SEQUENCE] => 2
                [STATUS] => CONFIRMED
                [SUMMARY] => My Holidays
                [TRANSP] => TRANSPARENT
            )
    
        [1] => Array
            (
                [DTSTART] => 20110112
                [DTEND] => 20110116
                [DTSTAMP] => 20110121T195741Z
                [UID] => a4@google.com
                [CREATED] => 20110119T142901Z
                [DESCRIPTION] => Project xyz Review Meeting MinutesAgenda1. Review of project version 1.0 requirements.2.Definitionof project processes.3. Review of project schedule.
                [Participants] =>  John Smith, Jane Doe, Jim Dandy-It wasdecided that the requirements need to be signed off byproduct marketing.-Project processes were accepted.-Project schedule needs to account for scheduled holidaysand employee vacation time. Check with HR for specificdates.-New schedule will be distributed by Friday.-Next weeks meeting is cancelled. No meeting until 3/23.
                [LAST-MODIFIED] => 20110119T152216Z
                [LOCATION] => 
                [SEQUENCE] => 2
                [STATUS] => CONFIRMED
                [SUMMARY] => test 11
                [TRANSP] => TRANSPARENT
            )
    
        [2] => Array
            (
                [DTSTART] => 20110119
                [DTEND] => 20110120
                [DTSTAMP] => 20110121T195741Z
                [UID] => a3@google.com
                [CREATED] => 20110119T141923Z
                [DESCRIPTION] => 
                [LAST-MODIFIED] => 20110119T141923Z
                [LOCATION] => 
                [SEQUENCE] => 0
                [STATUS] => CONFIRMED
                [SUMMARY] => test 6
                [TRANSP] => TRANSPARENT
            )
    
        [3] => Array
            (
                [DTSTART] => 20110119
                [DTEND] => 20110120
                [DTSTAMP] => 20110121T195741Z
                [UID] => a2@google.com
                [CREATED] => 20110119T141913Z
                [DESCRIPTION] => 
                [LAST-MODIFIED] => 20110119T141913Z
                [LOCATION] => 
                [SEQUENCE] => 0
                [STATUS] => CONFIRMED
                [SUMMARY] => test 4
                [TRANSP] => TRANSPARENT
            )
    
        [4] => Array
            (
                [DTSTART] => 20400201
                [DTEND] => 20400202
                [DTSTAMP] => 20400101T195741Z
                [UID] => a1@google.com
                [CREATED] => 20400101T141901Z
                [DESCRIPTION] => 
                [LAST-MODIFIED] => 20400101T141901Z
                [LOCATION] => 
                [SEQUENCE] => 0
                [STATUS] => CONFIRMED
                [SUMMARY] => Year 2038 problem test
                [TRANSP] => TRANSPARENT
            )
    
        [5] => Array
            (
                [DTSTART] => 19410512
                [DTEND] => 19410512
                [DTSTAMP] => 19410512T195741Z
                [UID] => a6@google.com
                [CREATED] => 20400101T141901Z
                [DESCRIPTION] => 
                [LAST-MODIFIED] => 20400101T141901Z
                [LOCATION] => 
                [SEQUENCE] => 0
                [STATUS] => CONFIRMED
                [SUMMARY] => Before 1970-Test: John Doe invents the Z3, the first digital Computer
                [TRANSP] => TRANSPARENT
            )
    
    )
