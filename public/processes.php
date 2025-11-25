<?php
/** @var array<string, array<string>> $groups */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Monitor - Bilo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.85rem;
        }
        .terminal-card {
            background-color: #2d2d2d;
            border: 1px solid #3e3e3e;
            border-radius: 6px;
            margin-bottom: 15px;
            height: 100%;
        }
        .terminal-header {
            background-color: #252526;
            padding: 8px 12px;
            border-bottom: 1px solid #3e3e3e;
            border-radius: 6px 6px 0 0;
            color: #4ec9b0;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .terminal-body {
            padding: 10px 12px;
        }
        .process-line {
            padding: 5px 8px;
            margin: 4px 0;
            background-color: #1e1e1e;
            border-left: 2px solid #007acc;
            border-radius: 3px;
            font-size: 0.75rem;
        }
        .process-command {
            color: #ce9178;
            margin-left: 8px;
            font-size: 0.7rem;
        }
        .btn-stop {
            background-color: #dc3545;
            border: none;
            font-size: 0.65rem;
            padding: 2px 8px;
            font-family: 'Courier New', Courier, monospace;
            margin-right: 8px;
        }
        .btn-stop:hover {
            background-color: #c82333;
        }
        .btn-add {
            background-color: #28a745;
            border: none;
            font-family: 'Courier New', Courier, monospace;
            margin-top: 8px;
            font-size: 0.75rem;
            padding: 4px 10px;
        }
        .btn-add:hover {
            background-color: #218838;
        }
        .pid-badge {
            background-color: #007acc;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            margin-left: 4px;
        }
        h1 {
            color: #4ec9b0;
            margin: 20px 0;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .no-processes {
            color: #858585;
            font-style: italic;
            padding: 8px;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-3">
        <h1>⚙️ Process Monitor</h1>

        <div class="row">
        <?php foreach (\Bilo\Enum\Queue::cases() as $q) :
            $name = $q->name;
            $processes = $groups[$name] ?? [];
        ?>
            <div class="col-md-6 mb-2">
            <div class="terminal-card">
                <div class="terminal-header" style="padding-top: 0;">
                    <a href="/index.php/monitor/add/<?= htmlspecialchars($name) ?>"
                       class="btn btn-success btn-sm btn-add">+</a>
                    <span style="margin-top: 10px;">
                    Queue: <?= htmlspecialchars($name)?> (<?php echo empty($processes)?0:count($processes)?>)
                    </span>
                </div>
                <div class="terminal-body">
                    <?php if (empty($processes)): ?>
                        <div class="no-processes">No running processes</div>
                    <?php else: ?>
                        <?php foreach ($processes as $process) :
                            $pid = substr($process, 0, strpos($process, ' '));
                            $command = substr($process, strpos($process, ' '));
                        ?>
                            <div class="process-line">
                                <a href="/index.php/monitor/stop/<?= htmlspecialchars($pid) ?>" 
                                   class="btn btn-danger btn-sm btn-stop"
                                   >STOP</a>
                                <span class="pid-badge">PID: <?= htmlspecialchars($pid) ?></span>
                                <span class="process-command"><?= htmlspecialchars($command) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>


                </div>
            </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
