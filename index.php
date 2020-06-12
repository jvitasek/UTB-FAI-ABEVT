<?php
ini_set('max_execution_time', '0');
const FILENAME_RESULTS = 'results.csv';

if(file_exists(FILENAME_RESULTS)) {
    unlink(FILENAME_RESULTS);
}

// to use in the graph
$minims = [];
$config = [
    'individuals' => 250,
    'dimensions' => 30,
    'min' => -512,
    'max' => 512,
    'cr' => 0.75,
    'f' => 0.6,
    'generations' => 5000
];
foreach(range(0, $config['dimensions']-1) as $r) {
    echo 'Run ' . ($r+1) . "\r\n";
    echo "Saving results to file...\r\n";
    $minims = differentialEvolution($config);
    $i = 0;
    foreach($minims as $result) {
        if($i === 0) {
            file_put_contents(FILENAME_RESULTS, ($r+1) . ',', FILE_APPEND);
        }
        file_put_contents(FILENAME_RESULTS, $result . ',', FILE_APPEND);
        $i++;
    }
    file_put_contents(FILENAME_RESULTS, $result . "\r\n", FILE_APPEND);
    echo "Saved\r\n\r\n";
}

// ALGOS

function differentialEvolution($config): array {

    $points = [];
    $minims = [];
    $genScores = [];

    // create population
    $pop = [];
    foreach(range(0, $config['individuals']-1) as $i) {
        foreach(range(0, $config['dimensions']-1) as $j) {
            $pop[$i][$j] = mt_rand($config['min'], $config['max']);
        }
    }

    for($generation = 0; $generation < $config['generations']; $generation++) {

        $newPop = [];
        $genScore = 0;

        for($g = 0; $g < $config['individuals']; $g++) {

            $point = [];
            $point['index'] = $generation;
            $parent = $pop[$g];

            $parent1 = chooseParent($parent, $pop);
            $parent2 = chooseParent($parent, $pop);
            $parent3 = chooseParent($parent, $pop);
            $mutant = mutation($parent1, $parent2, $parent3, $config['f'], $config['dimensions'], $config['min'], $config['max']);
            $child = copyCharacteristics($parent, $mutant, $config['cr'], $config['dimensions']);

            $childScore = schwefel($child, $config);
            $parentScore = schwefel($parent, $config);

            if($childScore <= $parentScore) {
                array_push($newPop, $child);
                $point['score'] = $childScore;
                $points[] = $point;
                $genScore += $childScore;
                $minims[] = $childScore;
            } else {
                array_push($newPop, $parent);
                $point['score'] = $parentScore;
                $points[] = $point;
                $genScore += $parentScore;
                $minims[] = $parentScore;
            }
        }

        $pop = $newPop;
        array_push($genScores, ($genScore/$config['individuals']));
    }

    return $genScores;
}

// TEST FUNCTIONS

function schwefel($pop, $config): float {
    $result = 0;
    foreach($pop as $individual) {
        if($config['min'] <= $individual && $individual <= $config['max']) {
            $result += -$individual*(sin(sqrt(abs($individual))));
        } else {
            throw new Exception();
        }
    }
    return $result;
}

// ALGO FUNCTIONS

function chooseParent($parent, $population) {
    $helpPop = $population;
    while(true) {
        $randIndex = array_rand($helpPop);
        $newParent = $helpPop[$randIndex];
        if($parent != $newParent) {
            return $newParent;
        }
    }
}

function mutation($parent1, $parent2, $parent3, $f, $dimensions, $min, $max) {
    $child = [];
    for($i = 0; $i < $dimensions; $i++) {
        $value = $parent1[$i] + $f * ($parent2[$i] - $parent3[$i]);
        if($value > $max) {
            array_push($child, $max);
        } elseif($value < $min) {
            array_push($child, $min);
        } else {
            array_push($child, $value);
        }
    }
    return $child;
}

function copyCharacteristics($parentX, $parentY, $cr, $dimensions) {
    $child = [];
    for($i = 0; $i < $dimensions; $i++) {
        if(rand(0, 1) < $cr) {
            array_push($child, $parentY[$i]);
        } else {
            array_push($child, $parentX[$i]);
        }
    }
    return $child;
}


// AUX

function writeLine($string): void {
    echo $string . "<br>";
}

function frand($min, $max, $decimals = 0) {
    $scale = pow(10, $decimals);
    return mt_rand($min * $scale, $max * $scale) / $scale;
}
