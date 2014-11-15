<?php

require_once 'Config.php';
require_once 'Mail.php';

  /**
   * UpdateMessage
   * 
   * 
   * @package N/A    
   * Constructs the file update 
   * messages sent to the Avis
   * development list 
   */

class UpdateMessage {

   private $from;
   private $host;
   private $port;
   private $user;
   private $pass;
   private $devl;

     /**
       * 
       * Constructor for UpdateMessage
       *
       * @param none
       * @return none
       */

   function __construct($args) {

      $configuration = new Config();

      $this->conf = $configuration->settings();
      $this->time = $configuration->newtime();
      $this->devl = $configuration->emaillist();

      $this->from = $this->conf['from'];
      $this->host = $this->conf['host'];
      $this->port = $this->conf['port'];
      $this->usel = $this->conf['user'];
      $this->pass = $this->conf['pass'];

      if ($args != null) {

         $this->update($args);

      }

   }

     /**
       * 
       * Sets and configures the 
       * update message
       *
       * @param $devel - the developer email
       * @param $msg - the message to send
       * @return none
       */

   function send($devel, $msg) {

      $subj = "FILE UPDATE";

      $headers = array(

         'From' => $this->from,
         'To' => $devel,
         'Subject' => $subj

      );

      $smtp = Mail::factory('smtp', array(

         'host' => $this->host,
         'port' => $this->port,
         'auth' => true,
         'username' => $this->usel,
         'password' => $this->pass
         

      ));

      $mail = $smtp->send($devel, $headers, $msg);       

   }

     /**
       * 
       * Sends the message to 
       * each user in the list
       *
       * @param $msg - the message to send
       * @return none
       */

   function update($msg) {

      for ($i = 0; $i < count($this->devl); ++$i) {

         $this->send($this->devl[$i], $msg);

      }

      return; 

   }

}

?>
