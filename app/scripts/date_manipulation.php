<?php
// Mirrors python-stack/app/www/scripts/date_manipulation.py.
// PHP's DateTime::modify('+1 month') overflows (Jan 31 + 1 month = Mar 3),
// so months/years are added by hand and the day is clamped like Python does.
// DateTimeImmutable is used throughout: its methods return a NEW date instead
// of changing the original (like Python's date objects).

// Adds (or subtracts, if negative) a number of months.
// e.g. 2024-01-31 + 1 month = 2024-02-29 (day capped at the month's last day).
function add_months(DateTimeImmutable $d, int $months): DateTimeImmutable
{
    // Count months from January = 0, so the maths works across year boundaries.
    // format('n') = month number 1-12, format('Y') = 4-digit year, format('j') = day.
    $monthIndex = (int) $d->format('n') - 1 + $months;
    // floor() rounds down, so -1 months from January moves back a year.
    $year = (int) $d->format('Y') + (int) floor($monthIndex / 12);
    // PHP's % can return a negative number, so add 12 and % again to keep it 0-11.
    $month = (($monthIndex % 12) + 12) % 12 + 1;
    // Use the original day unless the new month is shorter (e.g. 31 -> Feb).
    $day = min((int) $d->format('j'), cal_days_in_month_safe($year, $month));
    return $d->setDate($year, $month, $day);
}

// Adds (or subtracts) a number of years. Feb 29 becomes Feb 28 in a non-leap year.
function add_years(DateTimeImmutable $d, int $years): DateTimeImmutable
{
    $year = (int) $d->format('Y') + $years;
    $month = (int) $d->format('n');
    $day = min((int) $d->format('j'), cal_days_in_month_safe($year, $month));
    return $d->setDate($year, $month, $day);
}

// The calendar extension isn't installed in the image, so use date('t').
// Returns how many days a month has: format('t') gives the number of days in that month.
function cal_days_in_month_safe(int $year, int $month): int
{
    return (int) (new DateTimeImmutable())->setDate($year, $month, 1)->format('t');
}

// Called by the date form on index.html.
// Body: {"date": "2024-01-31", "amount": 1, "unit": "day" | "month" | "year"}
// Returns {"result": "2024-02-29"} or {"error": ..}
function adjust_date($body)
{
    $date = $body['date'] ?? null;
    // FILTER_VALIDATE_INT gives a whole number, or false (accepts 5 and "5", rejects "5.5").
    $amount = filter_var($body['amount'] ?? null, FILTER_VALIDATE_INT);
    $unit = $body['unit'] ?? null;

    // Parse "YYYY-MM-DD". The ! resets the time to midnight. Returns false if it doesn't match.
    $base = is_string($date) ? DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
    // PHP quietly rolls invalid dates over (2024-02-30 -> 2024-03-01), so format the
    // parsed date back and check it still matches what was sent.
    if ($base === false || $base->format('Y-m-d') !== $date || $amount === false || $unit === null) {
        return ['error' => 'invalid date, amount, or unit'];
    }

    // match is like a switch that returns a value (similar to Python's match/case).
    $result = match ($unit) {
        // Adding days has no end-of-month problem, so PHP's modify() is fine here.
        'day' => $base->modify("$amount days"),
        'month' => add_months($base, $amount),
        'year' => add_years($base, $amount),
        default => null,
    };
    if ($result === null) {
        return ['error' => 'unit must be day, month, or year'];
    }

    return ['result' => $result->format('Y-m-d')];
}
