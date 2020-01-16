<?php
/**
 * Created by PhpStorm.
 * User: oleg
 * Date: 14.01.20
 * Time: 8:46
 */
require_once ('connect.php');
class Nodes
{
    private $conn;


    private $id_move;

    private $title_move;

    private $lft_move;

    private $rgt_move;

    private $lvl_move;

    private $lvl_up;

    private $rgt_near;

    private $lft_near;

    private $id_edit = null;

    private $skew_tree;

    private $skew_lvl;

    private $skew_edit;

    private $id_branch;


    function __construct()
    {
        $db = DbConn::getInstance();
        $this->conn = $db->getDb();
    }


    public function addNode($id,$title)
    {

        $this->selectNode($id);

        $this->Validation($title);

        $this->conn->exec("UPDATE task SET rgt = rgt +2,
                              lft = IF(lft > $this->rgt_move, lft + 2, lft) WHERE rgt >= $this->rgt_move");

        $stmt = $this->conn->prepare('INSERT INTO task SET lft = ?,rgt = ? + 1,lvl = ? +1,
                                                                                    title = ?');

        if($stmt->execute(array($this->rgt_move, $this->rgt_move, $this->lvl_move,$title)))
        {
            echo "Узел удачно добален";
        }

    }

    public function deleteNode($id)
    {

        $this->selectNode($id);

        $stmt = $this->conn->prepare('
                DELETE FROM task WHERE lft >= ? AND rgt <= ?');

       if($stmt->execute(array($this->lft_move, $this->rgt_move)))
       {
           $this->Message("Node id $id has been deleted");

       }

       $this->conn->exec(" UPDATE task SET lft = IF(lft > $this->lft_move, 
               lft - ($this->rgt_move  - $this->lft_move + 1)  ,lft), 
               rgt = rgt - ($this->rgt_move  - $this->lft_move +1 )  
               WHERE rgt > $this->rgt_move ");


    }

    public function editNode($id,$title)
    {
        $this->selectNode($id);

        $this->Validation($title);

        $stmt = $this->conn->prepare("UPDATE task SET title = ? WHERE id = $id");

        if($stmt->execute(array($title))){
            $this->Message("id - $id Удачно изменен");
        }else{
            $this->Error("Что то пошло не так");
        }


    }

    public function movingNodeLeft($id_move,$id_branch)
    {

        $this->selectNode($id_move);

        $this->selectAddNodeParent($id_branch);

        $this->selectNodeBranch();

        $this->selectIdEdit();

        $this->id_edit .=   $this->id_move ;
        $this->skew_tree = $this->rgt_move - $this->lft_move + 1;
        $this->skew_lvl = $this->lvl_up - $this->lvl_move + 1;
        $this->skew_edit = $this->rgt_near - $this->lft_move + 1;


        if($this->lft_move < $this->lft_near && $this->rgt_move < $this->rgt_near  ){
            $this->Error(" Данную операцию выполнить не возможно,
                         возможно вы используете не правильный метод ");

        }

        if($id_branch == $this->id_branch){
            $this->Error(" Движение узла в родительский улез не возможно");
        }


        $this->conn->exec(" UPDATE task SET rgt = rgt + $this->skew_tree
                                   WHERE rgt < $this->lft_move AND rgt > $this->rgt_near ");

        $this->conn->exec(  " UPDATE task SET lft = lft + $this->skew_tree 
                                    WHERE lft < $this->lft_move AND lft > $this->rgt_near") ;

        $stmt = $this->conn->exec(
            "UPDATE task SET lft =lft + $this->skew_edit , rgt = rgt + $this->skew_edit
                                      , lvl = lvl +  $this->skew_lvl WHERE id IN($this->id_edit)");
        if($stmt > 0 ){
          $this->Message(" Удачно перемещенно $stmt узлов");
        }else{
            $this->Error("Что то пошло не так");
        }

    }

    public function movingNodeRight($id_move,$id_branch)
    {
        $this->selectNode($id_move);

        $this->selectAddNodeParent($id_branch);

        $this->selectNodeBranch();

        $this->selectIdEdit();

        $this->id_edit .=   $this->id_move ;
        $this->skew_tree = $this->rgt_move - $this->lft_move + 1;
        $this->skew_lvl = $this->lvl_up - $this->lvl_move + 1;
        $this->skew_edit = $this->rgt_near - $this->lft_move + 1 -  $this->skew_tree;


        if($this->lft_move > $this->lft_near && $this->rgt_move >= $this->rgt_near  ){
            $this->Error(" Данную операцию выполнить не возможно,
                         возможно вы используете не правильный метод ");

        }

        if($id_branch == $this->id_branch){
            $this->Error(" Движение узла в родительский улез не возможно");
        }


       $this->conn->exec(" UPDATE task SET rgt = rgt - $this->skew_tree
                              WHERE rgt > $this->rgt_move AND rgt < $this->rgt_near ");

       $this->conn->exec(
            " UPDATE task SET lft = lft - $this->skew_tree 
                              WHERE lft > $this->rgt_move AND lft <= $this->rgt_near");

       $stmt = $this->conn->exec("UPDATE task SET lft =lft + $this->skew_edit ,
              rgt = rgt + $this->skew_edit  , lvl = lvl +  $this->skew_lvl WHERE id IN($this->id_edit)");
        if($stmt > 0 ){
            $this->Message(" Удачно перемещенно $stmt узлов");
        }else{
            $this->Error("Что то пошло не так");
        }

    }

    public function movingNodeTop($id_move,$id_parent)
    {

        $this->selectNode($id_move);

        $stmt = $this->conn->prepare('SELECT  lvl  FROM task WHERE id  = ? ');
        $stmt->execute(array($id_parent));
        $result_p = $stmt->fetchAll();

        if(empty($result_p)){
            $this->Error( "Id $id_parent узла куда хотите переместить не найдет");
        }
        $this->lvl_up = $result_p[0]['lvl'];

        $stmt = $this->conn->prepare('SELECT  rgt  FROM task WHERE lft <= ? AND rgt >= ? 
                                                AND lvl= ? - 1 ');
        $stmt->execute(array($this->lft_move,$this->rgt_move,$this->lvl_move));
        $result = $stmt->fetchAll();
        $this->rgt_near = $result[0]['rgt'];

        $this->selectIdEdit();

        $this->id_edit .=   $this->id_move ;
        $this->skew_tree = $this->rgt_move - $this->lft_move + 1;
        $this->skew_lvl = $this->lvl_up - $this->lvl_move + 1;
        $this->skew_edit = $this->rgt_near - $this->lft_move + 1 -  $this->skew_tree;


        $stmt = $this->conn->prepare('SELECT  id FROM task WHERE lft <= ? AND rgt >= ? ');
        $stmt->execute(array($this->lft_move,$this->rgt_move));
        $result_parent = $stmt->fetchAll();
        $parent_id = null;
        foreach ($result_parent as $item)
        {
            foreach ($item as $value)
            {
                $parent_id[] = $value;
            }
        }

        if(!in_array($id_parent,$parent_id)){
            $this->Error("Перемещение в не родительскую ветку нельзя , пробуйте методы movingNodeLeft или 
            movingNodeRight");
        }


        $this->conn->exec(" UPDATE task SET rgt = rgt - $this->skew_tree
                              WHERE rgt > $this->rgt_move AND rgt <= $this->rgt_near ");
        $this->conn->exec(
                " UPDATE task SET lft = lft - $this->skew_tree
                              WHERE lft > $this->rgt_move AND lft <= $this->rgt_near");
        $stmt = $this->conn->exec("UPDATE task SET lft =lft + $this->skew_edit ,
              rgt = rgt + $this->skew_edit  , lvl = lvl +  $this->skew_lvl WHERE id IN($this->id_edit)");
        if($stmt > 0 ){
            $this->Message(" Удачно перемещенно $stmt узлов");
        }else{
            $this->Error("Что то пошло не так");
        }


    }


    public function selectNode($id)
    {
        $stmt = $this->conn->prepare('SELECT * FROM task WHERE id = ?');
        $stmt->execute(array($id));
        $result_insert = $stmt->fetchAll();

        if(empty($result_insert)){
            $this->Error("Id not found");
        }

        $this->id_move = $result_insert[0]['id'];
        $this->title_move = $result_insert[0]['title'];
        $this->rgt_move = $result_insert[0]['rgt'];
        $this->lft_move =$result_insert[0]['lft'];
        $this->lvl_move = $result_insert[0]['lvl'];

    }

    private function selectAddNodeParent($id)
    {
        $stmt = $this->conn->prepare('SELECT lft,rgt -1 AS rgt_near, lvl  FROM task
                                                                            WHERE id  = ? ');
        $stmt->execute(array($id));
        $result_b = $stmt->fetchAll();

        if(empty($result_b)){
            $this->Error("Id - $id узла к которому хотите переместить не найдет");
        }

        $this->lvl_up = $result_b[0]['lvl'];
        $this->rgt_near = $result_b[0]['rgt_near'];
        $this->lft_near = $result_b[0]['lft'];
    }

    private function selectNodeBranch()
    {
        $stmt = $this->conn->prepare('SELECT id FROM task 
                    WHERE lft <= ? AND rgt >= ?  AND lvl= ? - 1');
        $stmt->execute(array($this->lft_move,$this->rgt_move,$this->lvl_move));
        $result_parent = $stmt->fetchAll();

        $this->id_branch = $result_parent[0]['id'];
    }

    private function selectIdEdit()
    {
        $stmt = $this->conn->prepare('  SELECT id FROM task WHERE lft > ? AND rgt < ?');
        $stmt->execute(array($this->lft_move,$this->rgt_move));
        $res_edit = $stmt->fetchAll();

        foreach ($res_edit as $item)
        {
            $this->id_edit .= $item['id'].',';
        }
    }

    private function Error($message)
    {

        return exit($message);
    }

    private function Message($message)
    {
        echo"$message";
    }

    private function Validation($string)
    {

        if(strlen($string) <= 3 || strlen($string) > 25)
        {
            $this->Error("$string - Должна быть не меньше 3 и не больше 25!");
        }

    }




}





