<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class MemcacheTest extends AbstractBackendTest {
   protected function setUp() {
      if (!Matryoshka\Memcache::isAvailable()) {
         $this->markTestSkipped('Backend not available!');
      }

      return parent::setUp();
   }

   protected function getBackend() {
      return Matryoshka\Memcache::create($this->getMemcache());
   }

   public function testMemcache() {
      $backend = $this->getBackend();
      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($backend->set($key, $value, 1));
      // Wait for it to expire.
      sleep(2);

      $this->assertNull($backend->get($key));
   }

   /**
    * Memcache turns whitespace in keys into underscores, so we have to
    * exempt both chars from key equivalence tests.
    */
   protected function isCharExemptFromKeyEquivalence($char) {
      return $char === '_' || preg_match("/\s/", $char);
   }
}
