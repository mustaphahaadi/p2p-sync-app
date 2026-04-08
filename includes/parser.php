<?php
/**
 * Academic Calendar Parser
 * Smart Reminder System
 */

/**
 * Extracts events from raw text (usually pasted from a PDF).
 * 
 * Expected line format (rough match):
 * [Number] [Activity Description] [Date String containing a Year]
 * e.g., "1 Publication of centralized Timetable 16th Dec. 2025"
 * e.g., "22 Marking and uploading of continuous assessment scores 9th Mar. – 7th Apr. 2026"
 */
function parseAcademicCalendarText($rawText, $academicYear = '', $semester = '') {
    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $rawText));
    $events = [];

    // General month regex
    $monthsPattern = '(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?';
    
    // Pattern to look for date at the end of line, something starting with a number and ending with a 4-digit year.
    // e.g. "12th Jan – 27th Feb. 2026" or "12th – 17th Jan. 2026"
    // We look for the last occurrence of a date-like pattern ending the string.
    // This regex looks for: <Number><optional suffix><optional spaces or dashes or text><Year 20xx> at string end
    $datePattern = '/(\d{1,2}(?:st|nd|rd|th)?\s+(?:.*?)\s+20\d{2})\s*$/i';

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Try to match if line starts with a number (like the PDF format)
        if (preg_match('/^(\d+)\s+(.+)$/i', $line, $lineMatches)) {
            $number = $lineMatches[1];
            $remainder = trim($lineMatches[2]);

            if (preg_match($datePattern, $remainder, $dateMatch)) {
                $rawDate = $dateMatch[1];
                // Activity is what's left after stripping the date
                $activity = trim(str_replace($rawDate, '', $remainder));
                
                // Now try to structure the date
                $dates = parseDateRangeString($rawDate);

                if ($dates) {
                    $events[] = [
                        'title' => $activity,
                        'start_date' => $dates['start_date'],
                        'end_date' => $dates['end_date'],
                        'academic_year' => $academicYear,
                        'semester' => $semester,
                        'raw_date' => $rawDate
                    ];
                }
            }
        }
    }

    return $events;
}

/**
 * Parses raw date string into start and end standard Y-m-d MySQL dates.
 * e.g., "12th Jan – 27th Feb. 2026"
 * e.g., "12th – 17th Jan. 2026"
 */
function parseDateRangeString($str) {
    // Clean string (normalize dashes, remove dot from months for strtotime)
    $cleanStr = str_replace(['–', '—', '.'], ['-', '-', ''], $str);
    
    // Extract year, assume it's at the end
    preg_match('/(20\d{2})/', $cleanStr, $yearMatch);
    $year = $yearMatch[1] ?? date('Y');
    
    // Strip year
    $noYear = trim(str_replace($year, '', $cleanStr));
    
    if (strpos($noYear, '-') !== false) {
        // Range
        $parts = array_map('trim', explode('-', $noYear));
        $startStr = $parts[0];
        $endStr = $parts[1];
        
        // If start string doesn't have a month, it borrows from end string
        if (!preg_match('/[a-zA-Z]+/', $startStr)) { // No letters (no month)
            preg_match('/[a-zA-Z]+/', $endStr, $monthMatch);
            if (!empty($monthMatch[0])) {
                $startStr .= ' ' . $monthMatch[0];
            }
        }
        
        $startDate = date('Y-m-d', strtotime("$startStr $year"));
        // Evaluate end string (it might not have a year in it contextually for strtotime, but we add it if missing)
        $endDate = date('Y-m-d', strtotime("$endStr $year"));
        
        // Fallback for weird parsing
        if ($startDate === '1970-01-01' || !$startDate) $startDate = null;
        if ($endDate === '1970-01-01' || !$endDate) $endDate = null;

        return ['start_date' => $startDate, 'end_date' => $endDate];
    } else {
        // Single date
        $date = date('Y-m-d', strtotime("$noYear $year"));
        if ($date === '1970-01-01' || !$date) return null;
        
        return ['start_date' => $date, 'end_date' => null];
    }
}
