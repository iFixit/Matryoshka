<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Records statistics for cache calls.
 */
class Stats extends Backend {
   private $backend;
   private $stats;

   public function __construct(Backend $backend) {
      $this->backend = $backend;
      $this->stats = [
         'set_count' => 0,
         'set_time' => 0,
         'setMultiple_count' => 0,
         'setMultiple_key_count' => 0,
         'setMultiple_time' => 0,
         'add_count' => 0,
         'add_time' => 0,
         'increment_count' => 0,
         'increment_time' => 0,
         'decrement_count' => 0,
         'decrement_time' => 0,
         'get_count' => 0,
         'get_hit_count' => 0,
         'get_time' => 0,
         'getMultiple_count' => 0,
         'getMultiple_key_count' => 0,
         'getMultiple_hit_count' => 0,
         'getMultiple_time' => 0,
         'delete_count' => 0,
         'delete_time' => 0,
         'deleteMultiple_count' => 0,
         'deleteMultiple_key_count' => 0,
         'deleteMultiple_time' => 0,
      ];
   }

   public function getStats() {
      return $this->stats;
   }

   public function set($key, $value, $expiration = 0) {
      $start = microtime(true);
      $value = $this->backend->set($key, $value, $expiration);
      $end = microtime(true);

      $this->stats['set_count']++;
      $this->stats['set_time'] += $end - $start;

      return $value;
   }

   public function setMultiple(array $values, $expiration = 0) {
      $start = microtime(true);
      $value = $this->backend->setMultiple($values, $expiration);
      $end = microtime(true);

      $this->stats['setMultiple_count']++;
      $this->stats['setMultiple_key_count'] += count($values);
      $this->stats['setMultiple_time'] += $end - $start;

      return $value;
   }

   public function add($key, $value, $expiration = 0) {
      $start = microtime(true);
      $value = $this->backend->add($key, $value, $expiration);
      $end = microtime(true);

      $this->stats['add_count']++;
      $this->stats['add_time'] += $end - $start;

      return $value;
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      $start = microtime(true);
      $value = $this->backend->increment($key, $amount, $expiration);
      $end = microtime(true);

      $this->stats['increment_count']++;
      $this->stats['increment_time'] += $end - $start;

      return $value;
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      $start = microtime(true);
      $value = $this->backend->decrement($key, $amount, $expiration);
      $end = microtime(true);

      $this->stats['decrement_count']++;
      $this->stats['decrement_time'] += $end - $start;

      return $value;
   }

   public function get($key) {
      $start = microtime(true);
      $value = $this->backend->get($key);
      $end = microtime(true);

      $this->stats['get_count']++;
      $this->stats['get_time'] += $end - $start;

      if ($value !== self::MISS) {
         $this->stats['get_hit_count']++;
      }

      return $value;
   }

   public function getMultiple(array $keys) {
      $start = microtime(true);
      $value = $this->backend->getMultiple($keys);
      $end = microtime(true);

      $keyCount = count($keys);
      $missCount = count($value[1]);
      $this->stats['getMultiple_count']++;
      $this->stats['getMultiple_key_count'] += $keyCount;
      $this->stats['getMultiple_time'] += $end - $start;
      $this->stats['getMultiple_hit_count'] += $keyCount - $missCount;

      return $value;
   }

   public function delete($key) {
      $start = microtime(true);
      $value = $this->backend->delete($key);
      $end = microtime(true);

      $this->stats['delete_count']++;
      $this->stats['delete_time'] += $end - $start;

      return $value;
   }

   public function deleteMultiple(array $keys) {
      $start = microtime(true);
      $success = $this->backend->deleteMultiple($keys);
      $end = microtime(true);

      $this->stats['deleteMultiple_count']++;
      $this->stats['deleteMultiple_key_count'] += count($keys);
      $this->stats['deleteMultiple_time'] += $end - $start;

      return $success;
   }
}
