<?php


require_once "Nodes.php";


$task = new Nodes();
 if(!empty($argv[1]) ){

      $command = $argv[1] ? $argv[1] : null;
      $id = $argv[2] ? $argv[2] : null;
      $arg = $argv[3] ? $argv[3] : null;
  }else{
    return false;
 }


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

