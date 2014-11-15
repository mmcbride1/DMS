<?php

require_once 'constants.php';

  /**
   * Config
   * 
   * 
   * @package N/A    
   * Sets all the configurations
   * for the DocumentManager
   * application
   */

class Config {

     /**
       * 
       * Reads configuration and sets params.
       *
       * @param settings file (static)
       * @return array
       */

   function settings() {

      $file = constant('settings');

      $xml = simplexml_load_file($file);

      $settings = array(

      'passphrase' => $xml->passphrase,
      'command' => $xml->command,
      'datadir' => $xml->datadir,
      'importdir' => $xml->importdir,
      'faileddir' => $xml->faileddir,
      'archive' => $xml->archive,
      'logfile' => $xml->logfile,
      'from' => "<$xml->from>",
      'host' => $xml->host,
      'port' => $xml->port,
      'user' => $xml->user,
      'pass' => $xml->pass

      );

      return $settings;

   }

     /**
       * 
       * Sets current time
       *
       * @param date format (static)
       * @return string
       */

   function newtime() {

      date_default_timezone_set('America/New_York');

      $date = date('m/d/Y h:i:s');

      return str_replace(" ", "-", $date);

   }

     /**
       * 
       * Sets email configuration
       *
       * @param none
       * @return array
       */

  function emaillist() {

     $list = array(

        '0' => "mmcbride@spryinc.com",
        '1' => "jnacios@spryinc.com",
        '2' => "anastetsky@spryinc.com",
        '3' => "jhoppes@spryinc.com",
        '4' => "mdoo@spryinc.com"

     );

     return $list;

  }

     /**
       * 
       * Test case only
       *
       * @param none
       * @return array
       */

  function testlist() {

     $list = array(

        '0' => "mmcbride@spryinc.com",
        '1' => "mrmcbride@smcm.edu"

     );

     return $list;

  }

}

?>
