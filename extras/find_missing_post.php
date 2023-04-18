<?php

require_once('../wp-load.php');

$args = array(
    'numberposts' => -1,
    'category' => 8,
    'post_type' => 'post'
);

$count = 0;
$numbers = [];
foreach (get_posts($args) as $post) {

    preg_match('/Candy Crush Soda Saga Level (\d+) Tips/', $post->post_title, $matches);

    if (isset($matches[1])) {

        $numbers[] = intval($matches[1]);
    }
}

sort($numbers);

$missing = [];

for ($i = 1; $i < count($numbers); $i++) {
    if ($numbers[$i] - $numbers[$i - 1] > 1) {
        $diff = $numbers[$i] - $numbers[$i - 1];
        for ($j = 1; $j < $diff; $j++) {
            $missing[] = $numbers[$i - 1] + $j;
        }
    }
}
echo "<pre>";
print_r($missing);



