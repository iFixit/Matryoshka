<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Allows disabling gets/sets/deletes/etc.
 */
class Enable extends Backend {
   private $backend;
   public $getsEnabled;
   public $writesEnabled;
   public $deletesEnabled;

   public function __construct(Backend $backend) {
      $this->backend = $backend;
      $this->getsEnabled = true;
      $this->writesEnabled = true;
      $this->deletesEnabled = true;
   }

   public function set($key, $value, $expiration = 0) {
      if ($this->writesEnabled) {
         return $this->backend->set($key, $value, $expiration);
      } else {
         return false;
      }
   }

   public function setMultiple(array $values, $expiration = 0) {
      if ($this->writesEnabled) {
         return $this->backend->setMultiple($values, $expiration);
      } else {
         return false;
      }
   }

   public function add($key, $value, $expiration = 0) {
      if ($this->writesEnabled) {
         return $this->backend->add($key, $value, $expiration);
      } else {
         return false;
      }
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      if ($this->writesEnabled) {
         return $this->backend->increment($key, $amount, $expiration);
      } else {
         return false;
      }
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      if ($this->writesEnabled) {
         return $this->backend->decrement($key, $amount, $expiration);
      } else {
         return false;
      }
   }

   public function get($key) {
      if ($this->getsEnabled) {
         return $this->backend->get($key);
      } else {
         return self::MISS;
      }
   }

   public function getMultiple(array $keys) {
      if ($this->getsEnabled) {
         return $this->backend->getMultiple($keys);
      } else {
         $found = [];
         foreach ($keys as $key => $_) {
            $found[$key] = self::MISS;
         }

         return [$found, $keys];
      }
   }

   public function delete($key) {
      if ($this->deletesEnabled) {
         return $this->backend->delete($key);
      } else {
         return false;
      }
   }
}
