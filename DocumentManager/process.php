<?php

require_once 'DocumentManager.php';

  /**
   * Executes the managment 
   * process for the Avis
   * shared environment
   * 
   * @params none
   * return none
   * 
   */

/* create the manager object */

$new = new DocumentManager();

$msg = "";

/* complile update message */

$arr = $new->get();

if(!empty($arr)) {

   foreach($arr as $k => $v) {

      $msg .= "$v: $k\n";

   }

/* send updates */

$message = new UpdateMessage($msg);

}

?>
