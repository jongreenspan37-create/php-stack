<?php
// A list of practice SQL queries for the F1 data, shown in the dropdown on formula1.php.
// <<<'ML' ... ML is a "nowdoc": a multi-line string with no variable
// substitution (like Python's triple-quoted """...""").

/**
 * A catalogue of the F1 dataset practice queries.
 * Each entry: 'title' (short label), 'description' (what it demonstrates), 'sql' (the query).
 *
 * Usage:
 *   $queries = require __DIR__ . '/f1_queries.php';
 *   $rows = query_f1_data($queries[0]['sql']);
 */

return [
    [
        'title' => 'List all Constructors',
        'description' => 'Retrieve the names of all constructors.',
        'sql' => <<<'ML'
            SELECT DISTINCT Name
            FROM constructors
            ML,
    ],
    [
        'title' => 'Find All Drivers',
        'description' => 'List the names of all drivers in the dataset.',
        'sql' => <<<'ML'
            SELECT DISTINCT GivenName, FamilyName
            FROM drivers
            ML,
    ],
    [
        'title' => 'Count the Number of Constructors',
        'description' => 'How many constructors are there in the dataset?',
        'sql' => <<<'SQL'
            SELECT COUNT(DISTINCT ConstructorID) AS NumberOfConstructors
            FROM constructors
            SQL,
    ],
    [
        'title' => 'Count the Number of Drivers',
        'description' => 'How many drivers are there in the dataset?',
        'sql' => <<<'SQL'
            SELECT COUNT(DISTINCT DriverID) AS NumberOfDrivers
            FROM drivers
            SQL,
    ],
    [
        'title' => 'List All Races for a Specific Season',
        'description' => 'Retrieve all races that took place in the 2020 season.',
        'sql' => <<<'SQL'
            SELECT Season, Round, CircuitID
            FROM qualifying_results
            WHERE Season = 2020
            SQL,
    ],
    [
        'title' => 'Basic SELECT',
        'description' => 'Retrieve the Name and Nationality of all constructors.',
        'sql' => <<<'SQL'
            SELECT Name, Nationality
            FROM constructors
            SQL,
    ],
    [
        'title' => 'SELECT with Aliases',
        'description' => 'Retrieve driver GivenName and FamilyName and alias them as FirstName and LastName.',
        'sql' => <<<'SQL'
            SELECT GivenName AS FirstName, FamilyName AS LastName
            FROM drivers
            SQL,
    ],
    [
        'title' => 'SELECT Distinct Values',
        'description' => 'Retrieve distinct nationalities of drivers from the drivers table.',
        'sql' => <<<'SQL'
            SELECT DISTINCT Nationality
            FROM drivers
            SQL,
    ],
    [
        'title' => 'SELECT with Calculated Columns',
        'description' => 'Retrieve the Position and Points of drivers, and calculate their PointsPerPosition (Points divided by Position).',
        'sql' => <<<'SQL'
            SELECT Position, Points,
                   (Points * 1.0 / Position) AS PointsPerPosition
            FROM driver_standings
            SQL,
    ],
    [
        'title' => 'SELECT with Concatenation',
        'description' => 'Retrieve driver GivenName and FamilyName concatenated into a single column named FullName.',
        'sql' => <<<'SQL'
            SELECT CONCAT(GivenName, ' ', FamilyName) AS FullName
            FROM drivers
            SQL,
    ],
    [
        'title' => 'Basic Filtering',
        'description' => 'Retrieve all races where the Season is 2022.',
        'sql' => <<<'SQL'
            SELECT *
            FROM results
            WHERE Season = 2022
            SQL,
    ],
    [
        'title' => 'Filtering with Multiple Conditions',
        'description' => 'Retrieve drivers who are either German or British.',
        'sql' => <<<'SQL'
            SELECT *
            FROM drivers
            WHERE Nationality IN ('German', 'British')
            SQL,
    ],
    [
        'title' => 'Filtering with LIKE',
        'description' => "Retrieve all constructors whose name contains 'Ferrari'.",
        'sql' => <<<'SQL'
            SELECT *
            FROM constructors
            WHERE Name LIKE '%Ferrari%'
            SQL,
    ],
    [
        'title' => 'Filtering with IN',
        'description' => "Retrieve all results where the ConstructorID is either 'ferrari' or 'williams'.",
        'sql' => <<<'SQL'
            SELECT *
            FROM results
            WHERE ConstructorName IN ('ferrari', 'williams')
            SQL,
    ],
    [
        'title' => 'Filtering with Date',
        'description' => 'Retrieve all drivers who were born before 2000.',
        'sql' => <<<'SQL'
            SELECT *
            FROM drivers
            WHERE DateOfBirth < '2000-01-01'
            SQL,
    ],
    [
        'title' => 'Basic Sorting',
        'description' => 'Retrieve all races sorted by Season in ascending order.',
        'sql' => <<<'SQL'
            SELECT *
            FROM results
            ORDER BY Season ASC
            SQL,
    ],
    [
        'title' => 'Sorting with Multiple Columns',
        'description' => 'Retrieve all drivers sorted first by Nationality and then by GivenName.',
        'sql' => <<<'SQL'
            SELECT *
            FROM drivers
            ORDER BY Nationality ASC, GivenName ASC
            SQL,
    ],
    [
        'title' => 'Descending Order',
        'description' => 'Retrieve all results sorted by Points in descending order.',
        'sql' => <<<'SQL'
            SELECT *
            FROM results
            ORDER BY Points DESC
            SQL,
    ],
    [
        'title' => 'Sorting with NULLs',
        'description' => 'Retrieve all drivers and sort by DateOfBirth, placing NULL values last.',
        'sql' => <<<'SQL'
            SELECT *
            FROM drivers
            ORDER BY
              DateOfBirth IS NULL ASC,
              DateOfBirth ASC
            SQL,
    ],
    [
        'title' => 'Top N Results',
        'description' => 'Retrieve the top 5 drivers with the highest Points in the 2020 season.',
        'sql' => <<<'SQL'
            SELECT *
            FROM driver_standings
            WHERE Season = 2020
            ORDER BY Points DESC
            LIMIT 5
            SQL,
    ],
    [
        'title' => 'Basic LIMIT',
        'description' => 'Retrieve the first 10 constructors from the constructors table.',
        'sql' => <<<'SQL'
            SELECT *
            FROM constructors
            LIMIT 10
            SQL,
    ],
    [
        'title' => 'LIMIT with OFFSET',
        'description' => 'Retrieve 10 drivers starting from the 11th driver in the list.',
        'sql' => <<<'SQL'
            SELECT *
            FROM drivers
            ORDER BY driverId
            LIMIT 10 OFFSET 10
            SQL,
    ],
    [
        'title' => 'Top N Results with OFFSET',
        'description' => 'Retrieve the next 10 drivers after the top 5 drivers with the most Points in the 2021 season.',
        'sql' => <<<'SQL'
            SELECT *
            FROM results
            WHERE Season = 2021
            ORDER BY Points DESC
            LIMIT 10 OFFSET 5
            SQL,
    ],
    [
        'title' => 'LIMIT without ORDER BY',
        'description' => 'Retrieve the first 5 results of the 2020 season without specifying the order.',
        'sql' => <<<'SQL'
            SELECT *
            FROM results
            WHERE Season = 2020
            LIMIT 5
            SQL,
    ],
    [
        'title' => 'Pagination',
        'description' => 'Retrieve drivers for the 2020 season, showing results 11 through 20.',
        'sql' => <<<'SQL'
            SELECT *
            FROM results
            WHERE Season = 2020
            ORDER BY Points DESC
            LIMIT 10 OFFSET 10
            SQL,
    ],
    [
        'title' => 'SUM Function',
        'description' => 'Calculate the total Points scored in the 2024 season.',
        'sql' => <<<'SQL'
            SELECT SUM(Points) AS TotalPoints
            FROM results
            WHERE Season = 2024
            SQL,
    ],
    [
        'title' => 'AVG Function',
        'description' => 'Calculate the average Points scored by drivers in the 2000 season.',
        'sql' => <<<'SQL'
            SELECT AVG(Points) AS AveragePoints
            FROM results
            WHERE Season = 2000
            SQL,
    ],
    [
        'title' => 'MAX and MIN Functions',
        'description' => 'Find the maximum and minimum Points scored by a driver in the 2021 season.',
        'sql' => <<<'SQL'
            SELECT MAX(Points) AS MaxPoints, MIN(Points) AS MinPoints
            FROM results
            WHERE Season = 2021
            SQL,
    ],
    [
        'title' => 'COUNT Function',
        'description' => 'Count the number of races in the 2000 season.',
        'sql' => <<<'SQL'
            SELECT COUNT(DISTINCT CircuitID) as Race_Count
            FROM results
            WHERE Season = 2000
            SQL,
    ],
    [
        'title' => 'GROUP_CONCAT Function',
        'description' => 'List all drivers in each constructor, concatenated into a single column.',
        'sql' => <<<'SQL'
            SELECT r.ConstructorName,
                   GROUP_CONCAT(
                   DISTINCT CONCAT(d.GivenName, ' ', d.FamilyName) ORDER BY d.FamilyName ASC
                   )
                   AS Drivers
            FROM results r
            JOIN drivers d ON r.driverId = d.driverId
            GROUP BY r.ConstructorName
            SQL,
    ],
    [
        'title' => 'Basic GROUP BY',
        'description' => 'Retrieve the total Points scored by each constructor in the 2000 season.',
        'sql' => <<<'SQL'
            SELECT ConstructorName, SUM(Points) AS TotalPoints
            FROM results
            WHERE Season = 2000
            GROUP BY ConstructorName
            SQL,
    ],
    [
        'title' => 'GROUP BY with HAVING',
        'description' => 'Retrieve constructors that have more than 20 Points in the 2002 season.',
        'sql' => <<<'SQL'
            SELECT ConstructorName, SUM(Points) AS TotalPoints
            FROM results
            WHERE Season = 2002
            GROUP BY ConstructorName
            HAVING SUM(Points) > 20
            SQL,
    ],
    [
        'title' => 'COUNT with GROUP BY',
        'description' => 'Count the number of races each constructor participated in during 2020.',
        'sql' => <<<'SQL'
            SELECT ConstructorName, COUNT(*) AS NumberOfRaces
            FROM results
            WHERE Season = 2020
            GROUP BY ConstructorName
            SQL,
    ],
    [
        'title' => 'SUM with GROUP BY',
        'description' => 'Calculate the total Points for each driver, grouped by Nationality.',
        'sql' => <<<'SQL'
            SELECT d.Nationality, d.GivenName, d.FamilyName, SUM(r.Points) AS TotalPoints
            FROM results r
            JOIN drivers d ON r.DriverID = d.DriverID
            GROUP BY d.Nationality, d.GivenName, d.FamilyName
            SQL,
    ],
    [
        'title' => 'GROUP BY with Multiple Columns',
        'description' => 'Retrieve the average Points for each constructor and season combination.',
        'sql' => <<<'SQL'
            SELECT ConstructorName, Season, AVG(Points) AS AveragePoints
            FROM results
            GROUP BY ConstructorName, Season
            SQL,
    ],
    [
        'title' => 'Inner Join',
        // NOTE: original description said "2000 season" but the query filters Season = 2024 -- left as written.
        'description' => 'Retrieve driver names and their corresponding constructor names for races in the 2000 season.',
        'sql' => <<<'SQL'
            SELECT DISTINCT d.GivenName, d.FamilyName, r.ConstructorName
            FROM results r
            INNER JOIN drivers d ON r.DriverID = d.DriverID
            WHERE r.Season = 2024
            SQL,
    ],
    [
        'title' => 'Left Join',
        'description' => 'Retrieve all constructors and any drivers who raced for them (include constructors with no drivers).',
        'sql' => <<<'SQL'
            SELECT c.constructorId, d.GivenName, d.FamilyName
            FROM constructors c
            LEFT JOIN results r ON c.constructorId = r.ConstructorName
            LEFT JOIN drivers d ON r.DriverID = d.DriverID
            GROUP BY c.constructorId, d.GivenName, d.FamilyName
            SQL,
    ],
    [
        'title' => 'Right Join',
        'description' => 'Retrieve all results and the corresponding drivers for each result, including results with no drivers.',
        'sql' => <<<'SQL'
            SELECT r.Season, r.Round, r.CircuitID, d.GivenName, d.FamilyName
            FROM results r
            RIGHT JOIN drivers d ON r.DriverID = d.DriverID
            SQL,
    ],
    [
        'title' => 'Left Join (Drivers with No Results)',
        'description' => 'Retrieve a list of all drivers and their corresponding results, including drivers who have not participated in any races.',
        'sql' => <<<'SQL'
            SELECT d.GivenName, d.FamilyName, r.Position, r.Points
            FROM drivers d
            LEFT JOIN results r ON d.DriverID = r.DriverID
            ORDER BY d.GivenName, d.FamilyName
            SQL,
    ],
    [
        'title' => 'Join with Multiple Tables',
        'description' => 'Retrieve the GivenName, FamilyName, and ConstructorName for each driver, along with their total Points earned in the 2000 season.',
        'sql' => <<<'SQL'
            SELECT d.GivenName, d.FamilyName, c.constructorId, SUM(r.Points) AS TotalPoints
            FROM drivers d
            JOIN results r ON d.DriverID = r.DriverID
            JOIN constructors c ON r.ConstructorName = c.ConstructorID
            WHERE r.Season = 2000
            GROUP BY d.GivenName, d.FamilyName, c.constructorId
            SQL,
    ],
    [
        'title' => 'Simple Subquery',
        'description' => 'Retrieve drivers who have more points than the driver with the least points in the 2000 season.',
        'sql' => <<<'SQL'
            SELECT d.GivenName, d.FamilyName, SUM(r.Points) AS TotalPoints
            FROM drivers d
            JOIN results r ON d.DriverID = r.DriverID
            WHERE r.Season = 2000
            GROUP BY d.DriverID, d.GivenName, d.FamilyName
            HAVING SUM(r.Points) > (
              SELECT MIN(TotalPoints)
              FROM (
                SELECT SUM(r.Points) AS TotalPoints
                FROM results r
                WHERE r.Season = 2000
                GROUP BY r.DriverID
              ) AS DriverTotals
            )
            SQL,
    ],
    [
        'title' => 'Subquery in SELECT',
        'description' => 'Retrieve the GivenName and FamilyName of drivers along with their highest Points in any race.',
        'sql' => <<<'SQL'
            SELECT d.GivenName, d.FamilyName,
                   (SELECT MAX(r.Points)
                    FROM results r
                    WHERE r.DriverID = d.DriverID) AS HighestPoints
            FROM drivers d
            SQL,
    ],
    [
        'title' => 'Correlated Subquery',
        'description' => "Retrieve constructors and their drivers where the driver's Points is greater than the average Points for that constructor.",
        'sql' => <<<'SQL'
            SELECT
                c.constructorId,
                r.CircuitID,
                d.GivenName,
                d.FamilyName,
                r.Points,
                r.Season
            FROM
                constructors c
            JOIN
                results r ON c.constructorId = r.ConstructorName
            JOIN
                drivers d ON r.DriverID = d.DriverID
            WHERE
                r.Points > (
                    SELECT AVG(r2.Points)
                    FROM results r2
                    WHERE r2.ConstructorName = c.constructorId
                    GROUP BY r2.ConstructorName
                )
            SQL,
    ],
    [
        'title' => 'Simple CTE',
        'description' => 'Retrieve the average points scored per driver in the 2000 season. Uses a CTE to first calculate the total points scored by each driver, then calculates the average from this aggregated data.',
        'sql' => <<<'SQL'
            WITH DriverTotalPoints AS (
                SELECT
                    d.DriverID,
                    d.GivenName,
                    d.FamilyName,
                    SUM(r.Points) AS TotalPoints
                FROM
                    drivers d
                JOIN
                    results r ON d.DriverID = r.DriverID
                WHERE
                    r.Season = 2000
                GROUP BY
                    d.DriverID, d.GivenName, d.FamilyName
            )
            SELECT GivenName,
                FamilyName,
                TotalPoints,
                (SELECT AVG(TotalPoints) FROM DriverTotalPoints) AS AveragePoints
            FROM
                DriverTotalPoints
            SQL,
    ],
    [
        'title' => 'Complex CTE',
        'description' => 'Retrieve drivers who have finished in the top 3 positions in more than 5 races in the 2000 season, along with their average points per race.',
        'sql' => <<<'SQL'
            WITH Top3Races AS (
                SELECT
                    r.DriverID,
                    COUNT(*) AS Top3Count
                FROM
                    results r
                WHERE
                    r.Season = 2000 AND r.Position <= 3
                GROUP BY
                    r.DriverID
                HAVING
                    COUNT(*) > 5
            ),
            DriverPoints AS (
                SELECT
                    d.DriverID,
                    d.GivenName,
                    d.FamilyName,
                    AVG(r.Points) AS AvgPointsPerRace
                FROM
                    drivers d
                JOIN
                    results r ON d.DriverID = r.DriverID
                WHERE
                    r.Season = 2000
                GROUP BY
                    d.DriverID, d.GivenName, d.FamilyName
            )
            SELECT
                t.GivenName,
                t.FamilyName,
                t.AvgPointsPerRace
            FROM
                Top3Races tr
            JOIN
                DriverPoints t ON tr.DriverID = t.DriverID
            SQL,
    ],
];
