<?php

require_once 'MemcachedTest.php';

use iFixit\Matryoshka;

class McRouterTest extends MemcachedTest {
   protected function getBackend() {
      $memcached = new Memcached();
      $memcached->addServer('localhost', 11211);
      $memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, false);
      $memcached->setOption(Memcached::OPT_TCP_NODELAY, true);

      return Matryoshka\McRouter::create($memcached);
   }
}
