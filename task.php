<?php


require_once "Nodes.php";


$task = new Nodes();

$command = $argv[1];
$id = $argv[2];
$arg = $argv[3];

switch ($command) {
    case 'addNode':
      $task->addNode($id,$arg);
        break;
    case 'deleteNode':
        $task->deleteNode($id);
        break;
    case 'editNode':
        $task->editNode($id,$arg);
        break;
    case 'moveToLeft':
        $task->movingNodeLeft($id,$arg);
        break;
    case 'moveToRight':
        $task->movingNodeRight($id,$arg);
        break;
    case 'moveToTop':
        $task->movingNodeTop($id,$arg);
        break;
    default:
        echo "Command not found";
}

