<?php

/**
   * JobManager
   * 
   * 
   * @package DMS
   * Handles the scheduling for 
   * the DMS processes. With the 
   * arbitrary nature of file load 
   * and encryption/decryption times
   * it becomes important to prevent 
   * scheduled processes (jobs) 
   * from overlapping or influencing 
   * one another.
   */

class JobManager {

   /* hold configuration */

   var $conf;

   /**
    * Constructor
    * 
    **/

   function __construct() {

      $this->conf = $this->config();

      $this->pidexst();

   }

   /**
    * Load the 
    * configuration
    * file
    **/

   function config() {

      $ini = parse_ini_file('jobconf.ini');

      return $ini;

   }

   /**
    * Get the state
    * of the file 
    * being copied.
    * For scp purposes
    **/

   function size($cmd) {

       return explode(" ", exec($cmd));

   }

   /**
    * Encrypt control:
    * incase the next 
    * iteraton executes
    * while file is still
    * loading from source
    **/

   function doneloading($file) {

      $cmd = "ls -l $file";

      $s1 = $this->size($cmd);

      sleep(10);

      $s2 = $this->size($cmd);

      return ($s1[4] == $s2[4]);

   }
 
   /**
    * For cron control.
    * There is no way to
    * tell how long 
    * encryption will take,
    * so we don't want jobs
    * overlapping
    **/

   function prssflag() {

      $pid = $this->conf['pid'];

      $f = fopen($pid, 'w');

      fclose($f);

      return;

   }

   /**
    * Look for the
    * pid file and
    * exit if present
    * else continue
    **/

   function pidexst() {

      $f = $this->conf['pid'];

      if(file_exists($f)) {
        
         exit();

      }

      else {

         return;

      }

   }

   /**
    * Remove pid
    * file
    **/

   function killp() {

       unlink($this->conf['pid']);

   }
 
}

?>
