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

      return Matryoshka\Memcached::create($memcached);
   }

   public function testMemcached() {
      $backend = $this->getBackend();
      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($backend->set($key, $value, 1));
      // Wait for it to expire.
      sleep(2);

      $this->assertNull($backend->get($key));
   }
}
