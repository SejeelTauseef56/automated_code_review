<?php

function checkEvenOdd($number) {
    if ($number % 2 == 0) {
        echo "Even number";
    } else {
        echo "Odd number";
    }
}

$numbers = [1, 2, 3, 4, 5];
foreach ($numbers as $num) {
    // Nothing happens here
}

function calculateFactorial($num) {
    if ($num == 0) {
        return 1;
    }

    $result = 1;
    for ($i = $num; $i >= 1; $i--) {
        $result *= $i;
    }
    return $result;
}

$factorialResult = calculateFactorial('five');
echo "The factorial is: " . $factorialResult;

function multiplyNumbers($a, $b) {
    return $a * $b;
}

$number_of_items = 100;
$price_of_item = 20.5;

$total_cost = $number_of_items * $price_of_item;

echo "The total cost is: $" . $total_cost;

?>
