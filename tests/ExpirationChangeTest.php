<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class ExpirationChangeTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\ExpirationChange(new Matryoshka\Ephemeral(),
       function($expiration) {
          return $expiration * 2;
       });
   }

   public function testExpirationChange() {
      $mockBackend = new MockExpirationBackend();
      $cache = new Matryoshka\ExpirationChange($mockBackend,
       function($expiration) {
          return $expiration * 2;
       });

      $expiration = 5;
      $methods = [
         'set' => ['key1', 'value', $expiration],
         'setMultiple' => [['key2' => 'value1', 'key3' => 'value2'], $expiration],
         'add' => ['key4', 'value', $expiration],
         'increment' => ['key5', 1, $expiration]
      ];

      foreach ($methods as $method => $args) {
         $mockBackend->lastExpiration = null;

         // Poor man's splat.
         $arg1 = array_shift($args);
         $arg2 = array_shift($args);
         $arg3 = array_shift($args);
         $cache->$method($arg1, $arg2, $arg3);

         $this->assertSame($expiration * 2, $mockBackend->lastExpiration,
          "Method: {$method}");
      }
   }
}

class MockExpirationBackend extends Matryoshka\Ephemeral {
   public $lastExpiration;

   public function set($key, $value, $expiration = 0) {
      $this->lastExpiration = $expiration;
      return parent::set($key, $value, $expiration);
   }

   public function setMultiple(array $values, $expiration = 0) {
      $this->lastExpiration = $expiration;
      return parent::setMultiple($values, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      $this->lastExpiration = $expiration;
      return parent::add($key, $value, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      $this->lastExpiration = $expiration;
      return parent::increment($key, $amount, $expiration);
   }
}
