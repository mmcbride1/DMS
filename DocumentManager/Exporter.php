<?php

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

     $this->pidexst();

     /* run process */

     $this->carryout($this->conf['path2']);

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

      $this->prssflag();

      foreach(scandir($path) as $file) {

         $full = "$path$file";

         if('.' === $file || '..' === $file)

         continue;

         if($this->doneloading($full)) {

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

      unlink($this->conf['pid']);

      return;

  }  

}

?>
