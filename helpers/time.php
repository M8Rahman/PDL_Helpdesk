<?php
function timeTaken($created, $solved) {
    if (!$solved) return "—";

    $diff = strtotime($solved) - strtotime($created);
    if ($diff < 60) return $diff . " sec";

    $minutes = floor($diff / 60);
    if ($minutes < 60) return $minutes . " min";

    $hours = floor($minutes / 60);
    $mins  = $minutes % 60;

    return $hours . " hr " . $mins . " min";
}
