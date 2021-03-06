<?php

require_once 'Config.php';
require_once 'Format.php';
require_once 'constants.php';
require_once 'JobManager.php';
require_once 'UpdateMessage.php';

  /**
   * DocumentManager
   * 
   * 
   * @package DMS    
   * Scans the Avis import directory 
   * for any new files. If new files
   * are found, an initial decryption
   * test is carried out. If the file(s)
   * pass the test, a series of information
   * collecting functions are carried out
   * and then the file(s) are moved 
   * finally into the data share directory.  
   */

class DocumentManager {

   /* configuration variables */

   private $conf;
   private $time;
   private $pass;
   private $home;
   private $impt;
   private $fail;
   private $cmmd;
   private $arch;
   private $logf;
   private $cont;

   /* store file status here */

   var $arr;
   
   /* store job object here */
   
   private $job;
   
   /* store load status here */
   
   private $inprog = array();

     /**
       * 
       * Constructor for DocumentManager
       *
       * @param none
       * @return none
       */

   function __construct() {

      /* set constants */

      $this->cont = $this->constants();

      /* create the configuration object */

      $configuration = new Config();

      /* get the configuration settings */

      $this->conf = $configuration->settings();
      $this->time = $configuration->newtime();

      /* initalize all needed configurations info */

      $this->pass = $this->conf['passphrase'];
      $this->home = $this->conf['datadir']; 
      $this->impt = $this->conf['importdir'];
      $this->fail = $this->conf['faileddir'];
      $this->cmmd = $this->conf['command'];
      $this->arch = $this->conf['archive'];
      $this->logf = $this->conf['logfile'];

      /* check for destination directories */

      $this->createdir();

      /* go ahead and execute the directory scan 
       * and the decryption test 
       */

      $this->arr = $this->scandirectory();
      
      if(!empty($this->arr)) {
      
      $this->job = new JobManager();
      
      $this->arr = $this->test($this->arr);

      /* run the log write and update functions */

      $this->statmessg($this->impt);
      $this->updatedir($this->arr);
      
      }

   }

     /**
       * 
       * Gets the file statuses
       *
       * @param none
       * @return array
       */

   function get() {

      return $this->arr;
   
   }

     /**
       * 
       * Sets the program constants
       *
       * @param none
       * @return array
       */

   function constants() {

      $const = array(

         constant('rowcount'),
         constant('checksum'),
         constant('failsumv'),
         constant('faildctv'),
         constant('failform'),
         constant('passtest'),
         constant('faillogv'),
         constant('imptlogv'),
         constant('dconditn'),
         constant('chksumvl')

      );

     return $const; 

   }

     /**
       * 
       * simple way just to
       * get the file 
       * extention 
       *
       *
       * @param $file - the imported file
       * @param $m - mode: either 'd'
       * for a decrypted file ext or 
       * 'e' for an encrypted file ext
       * @return boolean
       */

   function ext($file, $m) {

      $ext = array(

         'DAT' => 'd',
         'txt' => 'd',
         'gpg' => 'e',
         'pgp' => 'e'

      );

      $fext = substr($file, -3, 3);

      if (in_array($fext, array_keys($ext, $m))) {

         return true;

      }

      else {

         return false;

      }

   }

     /**
       * 
       * Checks the import directory
       * for any new files. If none,
       * no need to run this program
       *
       * @param none
       * @return array
       */

   function scandirectory() {

      $listing = scandir($this->impt); 

      $exclude = array(".", "..");

      $listing = array_diff($listing, $exclude);      

      if(count($listing) > 0) {

         return $listing;

      }

      else {

         return;

      }

   }
 
     /**
       * 
       * Helper function for 'writelog()'
       * executes file write
       *
       * @param $msg - message to write
       * @return none
       */

   function logfile($file, $msg) {

      $log = "$this->logf/$file";

      file_put_contents($log, $msg, FILE_APPEND);

      return;

   }

     /**
       * 
       * If an import already exists
       * in the shared directory, it
       * is an update. Append the file
       * with a distinct date value
       * and move it to a stored location
       *
       * @param $file - file in the import directory.
       * @return none
       */

   function version($file) {

      $nfile = "$file-$this->time";

      $nfile = str_replace("/", "", $nfile);

      rename("$this->home/$file", "$this->arch/$nfile");

      return;

   }

     /**
       * 
       * Gets the cksum of the file
       *
       * @param $file - in the status array
       * @return cksum string
       */

   function checksum($file) {

      $path = $this->impt;

      $sum = exec("cksum $path/$file");

      $out = explode(' ', $sum);

      return $out[0];

   }

     /**
       * 
       * Gets the number of records 
       * for the file
       *
       * @param $file - in the status array
       * @return rowcount string
       */

   function rowcount($file) {

      $cmd = 'grep -c ".*"';

      $rows = exec("$cmd $this->impt/$file");

      return $rows;

   }

     /**
       * 
       * Brings the Avis cksum
       * list into process for
       * inspection 
       *
       * @param none
       * @return array
       */

   function avisumlist() {

      $out = array();

      $smfil = $this->cont[9];

      $in = file($smfil);

      for ($i = 0; $i < count($in); $i++) {

         $fsum = explode(":", $in[$i]);

         $out[$fsum[0]] = $fsum[1];

      }

      return $out;

   }

     /**
       * 
       * Reads the listing
       * returned @avisumlist
       * and ensures the given
       * file cksum value 
       * matches the official 
       * reported value
       *
       * @param $file - the imported file
       * @return boolean
       */

   function sumchk($file) {

      $exists = true;

      $list = $this->avisumlist();

      if (array_key_exists($file, $list)) {

         $osum = (int) $list[$file];

         $gsum = $this->checksum("$file");

         $exists = ($osum == $gsum) ? true : false;

      }

      return $exists;

   }

     /**
       * 
       * Carries out decryption on the
       * new file(s).  If the decryption
       * is carried out successfully, 
       * stage the file for inclusion in
       * the shared directory, otherwise,
       * move the file to a directory set
       * aside for corrupt files.  
       *
       * @param $file - the imported file
       * @return boolean
       */

   function decrypt($file) {

      $item = array();

      $path = $this->impt;

      $cmd = "echo $this->pass | $this->cmmd";

      $ext = array(".gpg", ".pgp");
 
      $name = str_replace($ext, "", $file);

      $cmd = "$cmd $path/$name --decrypt $path/$file";

      exec($cmd, $item);

      $con = $this->cont[8];

      $ok = ($item[8] == $con) ? true : false;

      return $ok;

   }

     /**
       * 
       * Reports the status of
       * each test carried
       * out on the impoted
       * files
       *
       * @param $score - the # of tests passed 
       * @param $file - the impoted file
       * @return none
       */

   function report($score, $file) {

      $file = "$file $this->time\n";

      ##########
      $upd = "";
      ##########

      switch($score) {

         case 0:

            $mesg = $this->cont[2]." $file";
            $this->logfile($this->cont[6], $mesg);
            $upd .= "$mesg\n";

            break;

         case 1:

            $mesg = $this->cont[3]." $file";
            $this->logfile($this->cont[6], $mesg);
            $upd .= "$mesg\n";

            break;

         case 2:

            $mesg = $this->cont[4]." $file";
            $this->logfile($this->cont[6], $mesg);
            $upd .= "$mesg\n";

            break;

         case 3:

            $mesg = $this->cont[5]." $file";
            $this->logfile($this->cont[6], $mesg);

            break;

      }

      /* send message */

      new UpdateMessage($upd);

      return;

   }

     /**
       * 
       * Carries out the tests
       * which designate whether 
       * each impoted file is
       * acceptable for entry 
       * into the shared directory
       * for developers
       *
       * @param none
       * @return array
       */

   function score($file) {

      $score = 0;

      if ($this->sumchk($file)) {

         $score += 1;

         if ($this->decrypt($file)) {

            $score += 1;

            $nf = substr($file, 0, -4);

            $fm = new Format("$this->impt/$nf");

            if ($fm->get()) {

               $score += 1;

            }

         }

      }

      $this->report($score, $file);

      return $score;

   }

     /**
       * 
       * Wrapper for test 
       * functionality.
       * Designates files 
       * based on outcome of
       * tests carried out on
       * imported files
       *
       * @param $dir - the import directory
       * @return array
       */

   function test($dir) {

      $pass = array();
      
      $cron = $this->job;
      
      $cron->prssflag();

      foreach ($dir as $file) {
      
      if($cron->doneloading("$this->impt/$file")) {
      
      foreach($this->inprog as $k => $v) {
      
      if($v == "$this->impt/$file") {
      
      unset($this->inprog[$k]);
      
        }
      
      }

      if ($this->ext($file, 'e')) {

      if ($this->score($file) == 3) {

      if(file_exists("$this->home/$file")) {

      $this->version($file);

      $stat = "update";

      } 

      else {

      $stat = "new";

      }

      $pass["$file"] = $stat;

      }

      else {

      rename("$this->impt/$file", "$this->fail/$file");

            }

         }

      }
      
      else {
      
      if(!in_array("$this->impt/$file", $this->inprog)) {
      
      array_push($this->inprog, "$this->impt/$file");
      
      continue;
      
        }
      
       }
      
      }
      
      $cron->killp();

      return $pass;

   }

     /**
       * 
       * Reports information about
       * the profiles of the imported
       * files that pass the initial 
       * tests
       *
       * @param $path - the import directory
       * @return none
       */

   function statmessg($path) {

      $arr = $this->scandirectory();
      
      $cron = $this->job;
      
      if(!empty($arr)) {

      foreach ($arr as $file) {
      
      if(!in_array("$this->impt/$file", $this->inprog)) {

      $name = $this->time;
      
      $impt = $this->cont[7];
      $smsg = $this->cont[1];
      $rmsg = $this->cont[0];

      $extn = "$this->fail/$file";

      if ($this->ext($file, 'e')) {

      $sumv = $this->checksum($file);

      $this->logfile($impt, "$file-$name\n");

      $this->logfile($impt, $smsg." $sumv\n");

      }

      else if ($this->ext($file, 'd')) {

      if (!(file_exists("$extn.gpg") || file_exists("$extn.pgp"))) {

      $rowv = $this->rowcount($file);

      $this->logfile($impt, $rmsg." $rowv\n");

      }

      unlink("$path/$file");

       }

      }
      
      else {continue;}
      
    }
   
   }

      return;

   }
   
     /**
       * 
       * Checks the returned file
       * list and updates the 
       * shared directory
       *
       * @param $filelist - filelist
       * @return none
       */ 

  function updatedir($filelist) {

     $current = $this->impt;
     $destint = $this->home;

     foreach ($filelist as $k => $v) {

        rename("$current/$k", "$destint/$k");

     }

     return;

  }

     /**
       * 
       * Checks to see if
       * the destination directories
       * exist, if not, create them
       *
       * @param none
       * @return none
       */

  function createdir() {

     if(!file_exists($this->arch)) {

        mkdir($this->arch, 0755, true);

     }

     if(!file_exists($this->fail)) {

        mkdir($this->fail, 0755, true);

     }

     return;

   }

 }

?>
