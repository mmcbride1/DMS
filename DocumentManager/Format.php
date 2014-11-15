<?php

 /**
   * Format
   * 
   * 
   * @package N/A    
   * Handles format testing
   * for imported files from
   * Avis
   */

class Format {

   /* true or false */

   var $ok;

     /**
       * 
       * Constructor for Format
       *
       * @param none
       * @return none
       */

   function __construct($file) {

      $this->results($file);

   }

     /**
       * 
       * Gets the file header
       * and determines the 
       * number of delimiters 
       * that should exist
       * throughout
       *
       * @param $file - the imported file
       * @return int
       */

   function gethdr($file) {

      $cmd = "head -n 1 $file";

      $exec = exec($cmd);

      $hdr = explode('|', $exec);

      if (end($hdr) == null) {

         array_pop($hdr);

      }

      $cols = count($hdr);

      $rows = 'grep -c ".*"';

      $rows = exec("$rows $file");

      return ($cols - 1) * $rows;

   }

     /**
       * 
       * Gets the number of 
       * delimiters in each
       * line of the imported
       * file
       *
       * @param $file - the imported file
       * @return int
       */

   function delcnt($file) {

      $out = array();

      $cmd = "awk 'BEGIN { FS = \"|\" } ; { print NF-1 }' $file";

      exec($cmd, $out);

      return array_sum($out);

   }

     /**
       * 
       * Updates the class
       * variable
       *
       * @param $file - the imported file
       * @return none
       */
 
   function results($file) {

      $a = $this->delcnt($file);
      $b = $this->gethdr($file);

      $this->ok = ($a == $b) ? true : false;

      return;

   }

     /**
       * 
       * Function that the
       * external program
       * will use to access
       * the results
       *
       * @param $file - the imported file
       * @return none
       */

   function get() {

      return $this->ok;

   }

 }

?>
