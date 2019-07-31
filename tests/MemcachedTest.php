<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class MemcachedTest extends AbstractBackendTest {
   protected function setUp() {
      if (!Matryoshka\Memcached::isAvailable()) {
         $this->markTestSkipped('Backend not available!');
      }

      return parent::setUp();
   }

   protected function getBackend() {
      $memcached = new Memcached();
      $memcached->addServer('localhost', 11211);
      $memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
      $memcached->setOption(Memcached::OPT_TCP_NODELAY, true);

      return Matryoshka\Memcached::create($memcached);
   }

   public function testExpiration() {
      $backend = $this->getBackend();
      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($backend->set($key, $value, 1));
      // Wait for it to expire.
      sleep(2);

      $this->assertNull($backend->get($key));
   }

   public function testFalse() {
      $backend = $this->getBackend();
      list($key1) = $this->getRandomKeyValue();

      $this->assertNull($backend->get($key1));
      $this->assertTrue($backend->set($key1, false));
      $this->assertFalse($backend->get($key1));
   }
}
