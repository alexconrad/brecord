<?php
/** @var array<string, array<string>> $groups */
?><html>
<body>

<?php
foreach (\Bilo\Enum\Queue::cases() as $q) :
    $name = $q->name;
    echo "<h2>$name</h2>";
    echo "<ul>";
    $processes = $groups[$name] ?? [];
    foreach ($processes as $process) :
        $pid = substr($process, 0, strpos($process, ' '));
        $command = substr($process, strpos($process, ' '));
        echo "<li><a href=\"/index.php/monitor/stop/$pid\">STOP $pid</a>: $command</li>";
    endforeach;
    echo "<li><a href=\"/index.php/monitor/add/$name\">Add new consumer</a></li>";
    echo "</ul>";
endforeach;

?>

</body>
</html>
