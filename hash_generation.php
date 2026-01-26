<?php

$users = [
    ['username' => 'PDLC1-7086', 'password' => '7086'],
    ['username' => 'PDLC1-9773', 'password' => '9773'],
    ['username' => 'PDLC1-20918', 'password' => '20918'],
    ['username' => 'PDLC1-21015', 'password' => '21015'],
    ['username' => 'PDLC1-30705', 'password' => '30705'],
    ['username' => 'PDLC1-30707', 'password' => '30707'],
    ['username' => 'PDLC1-30709', 'password' => '30709'],
    ['username' => 'PDLC1-30710', 'password' => '30710'],
    ['username' => 'PDLC1-30711', 'password' => '30711'],
    ['username' => 'PDLC1-30712', 'password' => '30712'],
    ['username' => 'PDLC1-30713', 'password' => '30713'],
    ['username' => 'PDLC1-30714', 'password' => '30714'],
    ['username' => 'PDLC1-30715', 'password' => '30715'],
    ['username' => 'PDLC1-30716', 'password' => '30716'],
    ['username' => 'PDLC1-30717', 'password' => '30717'],
    ['username' => 'PDLC1-30718', 'password' => '30718'],
    ['username' => 'PDLC1-30719', 'password' => '30719'],
    ['username' => 'PDLC1-30720', 'password' => '30720'],
];

$sql = "INSERT INTO `users`
(`username`, `password`, `role`, `status`, `remarks`, `created_at`, `updated_at`, `updated_by`)
VALUES\n";

$values = [];

foreach ($users as $u) {
    $hashed = password_hash($u['password'], PASSWORD_DEFAULT);
    $values[] = "('{$u['username']}', '{$hashed}', 'user', 'active', NULL, CURRENT_TIMESTAMP, NULL, NULL)";
}

$sql .= implode(",\n", $values) . ";";

echo $sql;