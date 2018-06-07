<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class McRouterTest extends AbstractBackendTest {
   protected function setUp() {
      if (!Matryoshka\McRouter::isAvailable()) {
         $this->markTestSkipped('Backend not available!');
      }

      return parent::setUp();
   }

   protected function getBackend() {
      $memcached = new Memcached();
      $memcached->addServer('localhost', 11211);
      $memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, false);
      $memcached->setOption(Memcached::OPT_TCP_NODELAY, true);

      return Matryoshka\McRouter::create($memcached);
   }

   public function testMcRouter() {
      $backend = $this->getBackend();
      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($backend->set($key, $value, 1));
      // Wait for it to expire.
      sleep(2);

      $this->assertNull($backend->get($key));
   }
}
