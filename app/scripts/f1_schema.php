<?php
// The F1 table definitions: table name => [column name => MySQL type].
// The column order must match the CSV files in csv/formula_1/, because
// get_formula_1() inserts each CSV row by position. Comments show each CSV header.

$f1_tables = [
    //season,round,position,points,wins,constructorId
    'constructor_standings' => [

        'season' => 'INT',
        'round' => 'TINYINT UNSIGNED',
        'position' => 'TINYINT UNSIGNED',
        'points' => 'DECIMAL(5,2)',
        'wins' => 'TINYINT UNSIGNED',
        'constructorId' => 'VARCHAR(255)',


    ],
    //year,constructorId,url,Name,Nationality
    'constructors' => [


        'year' => 'INT',
        'constructorId' => 'VARCHAR(50)',
        'url' => 'VARCHAR(255)',
        'name' => 'VARCHAR(100)',
        'nationality' => 'VARCHAR(50)',

    ],
    //season,round,position,points,wins,driverId
    'driver_standings' => [


        'season' => 'INT',
        'round' => 'TINYINT UNSIGNED',
        'position' => 'TINYINT UNSIGNED',
        'points' => 'DECIMAL(6,1)',
        'wins' => 'TINYINT UNSIGNED',
        'driverId' => 'VARCHAR(50)',

    ],
    //driverId,url,GivenName,FamilyName,DateOfBirth,Nationality
    'drivers' => [


        'driverId' => 'VARCHAR(50)',
        'url' => 'VARCHAR(255)',
        'givenName' => 'VARCHAR(100)',
        'familyName' => 'VARCHAR(100)',
        'dateOfBirth' => 'DATE',
        'nationality' => 'VARCHAR(50)',

    ],

    'pitstops' => [


        'season' => 'INT',
        'round' => 'TINYINT UNSIGNED',
        'circuitid' => 'VARCHAR(50)',
        'driverid' => 'VARCHAR(50)',
        'stop'     => 'INT',
        'lap'      => 'INT',
        'duration' => 'VARCHAR(20)',

    ],

    'results_history' => [


        'season'       => 'INT',
        'round'         => 'TINYINT UNSIGNED',
        'circuit_id'       => 'VARCHAR(255)',
        'country'         => 'VARCHAR(100)',
        'constructor_name' => 'VARCHAR(255)',
        'constructor_id'   => 'VARCHAR(255)',
        'constructor_nationality' => 'VARCHAR(255)',
        'driver_id'       => 'VARCHAR(255)',
        'driver_name'     => 'VARCHAR(255)',
        'driver_nationality' => 'VARCHAR(255)',
        'nationality'     => 'VARCHAR(255)',
        'position'        => 'INT',
        'points'          => 'FLOAT',
        'grid'            => 'INT',
        'status'          => 'VARCHAR(255)',
        'average_speed'   => 'FLOAT',
        'difference_grid_position' => 'INT',
        'won_last_race'   => 'INT',
        'won_last_2_races' => 'INT',
        'won_last_3_races' => 'INT',
        'won_last_4_races' => 'INT',
        'won_last_5_races' => 'INT',
        'won_last_6_races' => 'INT',
        'won_last_7_races' => 'INT',
        'won_last_10_races' => 'INT',
        'top_3_last_5_races' => 'INT',
        'top_5_last_10_races' => 'INT',
    ],

    //Season,Round,CircuitID,DriverID,Code,PermanentNumber,GivenName,FamilyName,DateOfBirth,driver_nationality,ConstructorID,ConstructorName,constructor_nationality,Q1,Q2,Q3,avg_Q1_time,avg_Q2_time,avg_Q3_time,driver_avg_position,driver_total_races,avg_constructor_position,was_first_last_1,was_first_last_2,was_first_last_3,was_first_last_4,was_first_last_5,was_top3_last_1,was_top3_last_2,was_top3_last_3,was_top3_last_4,was_top3_last_5,was_top5_last_1,was_top5_last_2,was_top5_last_3,was_top5_last_4,was_top5_last_5,was_top8_last_1,was_top8_last_2,was_top8_last_3,was_top8_last_4,was_top8_last_5,was_top10_last_1,was_top10_last_2,was_top10_last_3,was_top10_last_4,was_top10_last_5,was_top15_last_1,was_top15_last_2,was_top15_last_3,was_top15_last_4,was_top15_last_5,Position
    'results_qualy' => [


        'season' => 'INT',
        'round'     => 'TINYINT UNSIGNED',
        'circuitid' => 'VARCHAR(50)',
        'driverid'  => 'VARCHAR(50)',
        'code'      => 'VARCHAR(3)',
        'permanentnumber' => 'TINYINT UNSIGNED',
        'givenname' => 'VARCHAR(100)',
        'familyname' => 'VARCHAR(100)',
        'dateofbirth' => 'DATE',
        'driver_nationality' => 'VARCHAR(50)',
        'constructorid' => 'VARCHAR(50)',
        'constructorname' => 'VARCHAR(50)',
        'constructor_nationality' => 'VARCHAR(50)',
        'q1' => 'VARCHAR(20)',
        'q2' => 'VARCHAR(20)',
        'q3' => 'VARCHAR(20)',
        'avg_q1_time' => 'DECIMAL(10,4)',
        'avg_q2_time' => 'DECIMAL(10,4)',
        'avg_q3_time' => 'DECIMAL(10,4)',
        'driver_avg_position' => 'DECIMAL(5,2)',
        'driver_total_races' => 'INT',
        'avg_constructor_position' => 'DECIMAL(5,2)',
        'was_first_last_1' => 'TINYINT(1)',
        'was_first_last_2' => 'TINYINT(1)',
        'was_first_last_3' => 'TINYINT(1)',
        'was_first_last_4' => 'TINYINT(1)',
        'was_first_last_5' => 'TINYINT(1)',
        'was_top3_last_1' => 'TINYINT(1)',
        'was_top3_last_2' => 'TINYINT(1)',
        'was_top3_last_3' => 'TINYINT(1)',
        'was_top3_last_4' => 'TINYINT(1)',
        'was_top3_last_5' => 'TINYINT(1)',
        'was_top5_last_1' => 'TINYINT(1)',
        'was_top5_last_2' => 'TINYINT(1)',
        'was_top5_last_3' => 'TINYINT(1)',
        'was_top5_last_4' => 'TINYINT(1)',
        'was_top5_last_5' => 'TINYINT(1)',
        'was_top8_last_1' => 'TINYINT(1)',
        'was_top8_last_2' => 'TINYINT(1)',
        'was_top8_last_3' => 'TINYINT(1)',
        'was_top8_last_4' => 'TINYINT(1)',
        'was_top8_last_5' => 'TINYINT(1)',
        'was_top10_last_1' => 'TINYINT(1)',
        'was_top10_last_2' => 'TINYINT(1)',
        'was_top10_last_3' => 'TINYINT(1)',
        'was_top10_last_4' => 'TINYINT(1)',
        'was_top10_last_5' => 'TINYINT(1)',
        'was_top15_last_1' => 'TINYINT(1)',
        'was_top15_last_2' => 'TINYINT(1)',
        'was_top15_last_3' => 'TINYINT(1)',
        'was_top15_last_4' => 'TINYINT(1)',
        'was_top15_last_5' => 'TINYINT(1)',
        'position' => 'TINYINT UNSIGNED',



    ],
    //Season,Round,CircuitID,Position,Points,DriverID,Code,PermanentNumber,GivenName,FamilyName,DateOfBirth,Nationality,ConstructorName,ConstructorNationality,Grid,Laps,Status,Time,FastestLapTime,FastestLapLap,AverageSpeed
    'results' => [


        'season' => 'INT',
        'round' => 'TINYINT UNSIGNED',
        'circuitid' => 'VARCHAR(50)',
        'position' => 'TINYINT UNSIGNED',
        'points' => 'DECIMAL(5,2)',
        'driverid' => 'VARCHAR(50)',
        'code' => 'VARCHAR(3)',
        'permanentnumber' => 'TINYINT UNSIGNED',
        'givenname' => 'VARCHAR(100)',
        'familyname' => 'VARCHAR(100)',
        'dateofbirth' => 'DATE',
        'nationality' => 'VARCHAR(50)',
        'constructorname' => 'VARCHAR(50)',
        'constructor_nationality' => 'VARCHAR(50)',
        'grid' => 'TINYINT UNSIGNED',
        'laps' => 'TINYINT UNSIGNED',
        'status' => 'VARCHAR(50)',
        'time' => 'VARCHAR(20)',
        'fastestlaptime' => 'VARCHAR(20)',
        'fastestlaplap' => 'TINYINT UNSIGNED',
        'averagespeed' => 'DECIMAL(10,4)',

    ]
];
