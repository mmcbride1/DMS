<?php

require_once 'JobManager.php';
require_once 'UpdateMessage.php';

 /**
   * Exporter
   * 
   * 
   * @package DMS
   * Scans the Oracle export directory 
   * for any new files. If new files
   * are found, encryption is carried 
   * out and the files are moved to
   * the fileshare. Failed encryptions
   * are logged and seperated
   */

class Exporter {

   /* hold configuration */

   var $conf;

   /* hold job object */

   private $job;
   
   /* hold export updates */
   
   private $scan = array();

   /**
    * Construct
    *
    * load the configuration
    * and execute the directory
    * scan
    **/

   function __construct() {

     /* set configuration */

     $this->conf = $this->config(); 

     /* check if process is running */

     $this->job = new JobManager();

     /* run process */

     $this->carryout($this->conf['path2']);
     
     /* mail updates */
     
     $this->get();

   }

   /**
    * Load the 
    * configuration
    * file
    **/

   function config() {

      $ini = parse_ini_file('exports.ini');

      return $ini;

   }

   /**
    * Check if there 
    * are files in the
    * export directory
    * If not, no reason 
    * to run this program
    **/

   function dirc($path) {

      $f = scandir($path);

      if(count($f) <= 2) {
       
         exit();
      
      }

      return;

    }
    
   /**
    * For every export 
    * file that is 
    * successfully 
    * encrypted, send a
    * developer update
    **/
    
    function get() {
    
       $upd = $this->scan;
       
       if(count($upd) > 0) {
       
          $msg = $this->conf['msg']."\n";
          
          foreach($upd as $u) {
          
             $msg .= "$f\n";
          
          }
          
          $alert = new UpdateMessage($msg);
       
       }
       
       return;
    
    }

   /**
    * Encrypt the
    * files according
    * to the given gpg
    * key. Pipe stderr
    * to the php log
    * file 
    **/

   function encrypt($file) {

      $cmd = $this->conf['cmd']. $file;

      $tunnels = array(

         0 => array('pipe','r'), 
         1 => array('pipe','w'), 
         2 => array('pipe','w') 

      );

      /* I/O */

      $i = array();

      $r = proc_open($cmd, $tunnels, $i);

      fclose($i[1]);

      $errors = stream_get_contents($i[2]);

      fclose($i[2]);

      $r2 = proc_close($r);

      if($r2 != 0) {

         error_log($errors);
      
      }
      
      return $r2;

   }

   /**
    * Move the file 
    * to the shared 
    * data directory
    **/

   function move($full, $file) {

      $share = $this->conf['path1'];
      
      array_push($this->scan, "$file");

      rename("$full.gpg", "$share$file.gpg");

      return;

   }

   /**
    * Encrypt, 
    * delete and
    * move the files
    **/

   function carryout($path) {

      $this->dirc($path);

      $cron = $this->job;

      $cron->prssflag();

      foreach(scandir($path) as $file) {

      $full = "$path$file";

      if('.' === $file || '..' === $file)

      continue;

      if($cron->doneloading($full)) {

      $r = $this->encrypt($full);

      if($r == 0) {

      unlink($full);

      $this->move($full, $file);

      }

      else {

      $fail = $this->conf['path3'];
            
      rename($full, "$fail$file");

        }

      }

      else {continue;}

    }

      $cron->killp();

      return;

  }  

}

?>
