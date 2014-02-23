<?php

require_once __DIR__ . '/../library/iFixit/Smeagol.php';

\iFixit\Smeagol::autoload();

class SmeagolTest extends PHPUnit_Framework_TestCase {
   public function testMemoryArray() {
      $cache = new \iFixit\Smeagol\Backends\MemoryArray();

      $key = 'test';
      $value = 'value';
      $this->assertNull($cache->get($key));

      $cache->set($key, $value);

      $this->assertSame($value, $cache->get($key));

      $cache->delete($key);

      $this->assertNull($cache->get($key));
   }

   public function testHierarchy() {
      $backends = [
         new \iFixit\Smeagol\Backends\MemoryArray(),
         new \iFixit\Smeagol\Backends\MemoryArray()
      ];

      $hierarchy = new \iFixit\Smeagol\Backends\Hierarchy($backends);

      $allBackends = array_merge($backends, [$hierarchy]);

      $key = 'test';
      $value = 'value';
      $this->assertNull($hierarchy->get($key));

      $hierarchy->set($key, $value);

      foreach ($allBackends as $backend) {
         $this->assertSame($value, $backend->get($key));
      }

      $backends[0]->delete($key);

      $this->assertSame($value, $hierarchy->get($key));

      $this->assertSame($value, $backends[0]->get($key));

      $hierarchy->delete($key);

      foreach ($allBackends as $backend) {
         $this->assertNull($backend->get($key));
      }
   }
}
