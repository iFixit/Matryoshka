<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * A convenience class for making wrapping backends who's functions fall
 * through to the underlying backend when they aren't implemented.
 *
 * Makes it easier to implement one or two of the functions without having to
 * do all of them.
 */
abstract class BackendWrap extends Backend {
   protected $backend;

   public function __construct(Backend $backend) {
      $this->backend = $backend;
   }

   public function set($key, $value, $expiration = 0) {
      return $this->backend->set($key, $value, $expiration);
   }

   public function setMultiple(array $values, $expiration = 0) {
      return $this->backend->setMultiple($values, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return $this->backend->add($key, $value, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      return $this->backend->increment($key, $amount, $expiration);
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      return $this->backend->decrement($key, $amount, $expiration);
   }

   public function get($key) {
      return $this->backend->get($key);
   }

   public function getMultiple(array $keys) {
      return $this->backend->getMultiple($keys);
   }

   public function delete($key) {
      return $this->backend->delete($key);
   }
}
