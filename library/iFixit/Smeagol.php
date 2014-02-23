<?php

namespace iFixit;

use iFixit\Smeagol;

class Smeagol {
   /**
    * Setup autoloader for Smeagol library classes.
    */
   public static function autoload() {
      spl_autoload_register(function($class) {
         $prefix = __CLASS__ . '\\';
         if (strpos($class, $prefix) === 0) {
            // Remove vendor from name.
            $class = substr($class, strlen(__NAMESPACE__) + 1);
            // Convert namespace separator to directory ones.
            $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
            // Prefix with this file's directory.
            $class = __DIR__ . DIRECTORY_SEPARATOR . $class;

            require "$class.php";

            return true;
         }

         return false;
      });
   }
}
