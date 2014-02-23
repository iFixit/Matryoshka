<?php

require_once __DIR__ . '/../library/iFixit/Smeagol.php';

\iFixit\Smeagol::autoload();

class SmeagolTest extends PHPUnit_Framework_TestCase {
   public function testMemoryArray() {
      $cache = new \iFixit\Smeagol\Backends\MemoryArray();
      list($key, $value) = $this->getRandomKeyValue();

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
      list($key, $value) = $this->getRandomKeyValue();

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

   public function testPrefixed() {
      $memoryCache = new \iFixit\Smeagol\Backends\MemoryArray();
      $prefix = 'prefix';
      $prefixedCache = new \iFixit\Smeagol\Backends\Prefixed($memoryCache,
       $prefix);
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($prefixedCache->get($key));

      $prefixedCache->set($key, $value);

      $this->assertSame($value, $prefixedCache->get($key));
      $this->assertSame($value, $memoryCache->get("{$prefix}{$key}"));
      $this->assertNull($memoryCache->get($key));

      $prefixedCache->delete($key);

      $this->assertNull($prefixedCache->get($key));
      $this->assertNull($memoryCache->get("{$prefix}{$key}"));
      $this->assertNull($memoryCache->get($key));
   }

   public function testMemcached() {
      $memcache = new Memcache();
      $memcache->pconnect('localhost', 11211);
      $cache = new \iFixit\Smeagol\Backends\Memcached($memcache);
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($cache->get($key));

      $cache->set($key, $value);

      $this->assertSame($value, $cache->get($key));

      $cache->delete($key);

      $this->assertNull($cache->get($key));
   }

   public function testgetAndSet() {
      $cache = new \iFixit\Smeagol\Backends\MemoryArray();
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($cache->get($key));

      $hit = false;
      $callback = function() use ($value, &$hit) {
         $hit = true;
         return $value;
      };

      $getAndSetValue = $cache->getAndSet($key, $callback);

      $this->assertTrue($hit);
      $this->assertSame($value, $getAndSetValue);
      $this->assertSame($value, $cache->get($key));

      $hit = false;
      $getAndSetValue = $cache->getAndSet($key, $callback);

      $this->assertFalse($hit);
      $this->assertSame($value, $getAndSetValue);
      $this->assertSame($value, $cache->get($key));
   }

   public function getRandomKeyValue() {
      return [
         'key-' . microtime(true) * 100,
         'value-' . microtime(true) * 100
      ];
   }
}
