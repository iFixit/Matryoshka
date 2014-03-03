<?php

require_once __DIR__ . '/../library/iFixit/Matryoshka.php';

use iFixit\Matryoshka;

Matryoshka::autoload();

class MatryoshkaTest extends PHPUnit_Framework_TestCase {
   public function testHierarchy() {
      $backends = [
         new Matryoshka\MemoryArray(),
         new Matryoshka\Memcached($this->getMemcached())
      ];
      $hierarchy = new Matryoshka\Hierarchy($backends);
      $allBackends = array_merge($backends, [$hierarchy]);
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($hierarchy->get($key));

      $this->assertTrue($hierarchy->set($key, $value));

      foreach ($allBackends as $backend) {
         $this->assertSame($value, $backend->get($key));
      }

      $this->assertTrue($backends[0]->delete($key));

      $this->assertSame($value, $hierarchy->get($key));
      $this->assertSame($value, $backends[0]->get($key));

      $this->assertTrue($hierarchy->delete($key));

      foreach ($allBackends as $backend) {
         $this->assertNull($backend->get($key));
      }

      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($hierarchy->set($key, $value));

      $this->assertTrue($backends[1]->delete($key));
      $this->assertSame($value, $hierarchy->get($key));
   }

   public function testPrefixed() {
      $memoryCache = new Matryoshka\MemoryArray();
      $prefix = 'prefix';
      $prefixedCache = new Matryoshka\Prefixed($memoryCache,
       $prefix);
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($prefixedCache->get($key));

      $this->assertTrue($prefixedCache->set($key, $value));

      $this->assertSame($value, $prefixedCache->get($key));
      $this->assertSame($value, $memoryCache->get("{$prefix}{$key}"));
      $this->assertNull($memoryCache->get($key));

      $this->assertTrue($prefixedCache->delete($key));

      $this->assertNull($prefixedCache->get($key));
      $this->assertNull($memoryCache->get("{$prefix}{$key}"));
      $this->assertNull($memoryCache->get($key));
   }

   public function testScoped() {
      $memoryCache = new Matryoshka\MemoryArray();
      $scope = 'scope';
      $scopedCache = new Matryoshka\Scoped($memoryCache,
       $scope);
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      $this->assertNull($scopedCache->get($key1));

      $this->assertTrue($scopedCache->set($key1, $value1));

      $this->assertSame($value1, $scopedCache->get($key1));

      $this->assertTrue($scopedCache->delete($key1));

      $this->assertNull($scopedCache->get($key1));

      $this->assertTrue($scopedCache->set($key1, $value1));
      $this->assertTrue($scopedCache->set($key2, $value2));

      $this->assertSame($value1, $scopedCache->get($key1));
      $this->assertSame($value2, $scopedCache->get($key2));

      $this->assertTrue($scopedCache->deleteScope());

      $this->assertNull($scopedCache->get($key1));
      $this->assertNull($scopedCache->get($key2));

      $this->assertTrue($scopedCache->set($key1, $value1));

      $this->assertSame($value1, $scopedCache->get($key1));
   }

   public function testMemcached() {
      $cache = new Matryoshka\Memcached($this->getMemcached());
      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($cache->set($key, $value, 1));
      // Wait for it to expire.
      sleep(2);

      $this->assertNull($cache->get($key));
   }

   public function testEnabled() {
      $cache = new Matryoshka\Enabled(new Matryoshka\MemoryArray());
      list($key, $value) = $this->getRandomKeyValue();

      $cache->setsEnabled = false;
      $this->assertFalse($cache->set($key, $value));
      $this->assertNull($cache->get($key));
      $cache->setsEnabled = true;

      $this->assertTrue($cache->set($key, $value));
      $this->assertSame($value, $cache->get($key));

      $cache->getsEnabled = false;
      $this->assertNull($cache->get($key));
      $cache->getsEnabled = true;

      $cache->deletesEnabled = false;
      $this->assertFalse($cache->delete($key));
      $this->assertSame($value, $cache->get($key));
      $cache->deletesEnabled = true;
   }

   /**
    * It's hard to test them individually so we can just test all 3 at once.
    */
   public function testgetSetDelete() {
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();
      list($key3, $value3) = $this->getRandomKeyValue();
      foreach ($this->getAllBackends() as $type => $cache) {
         $this->assertNull($cache->get($key1), $type);
         $this->assertTrue($cache->set($key1, $value1), $type);
         $this->assertSame($value1, $cache->get($key1), $type);
         $this->assertTrue($cache->set($key1, $value1), $type);
         $this->assertTrue($cache->delete($key1), $type);
         $this->assertFalse($cache->delete($key1), $type);
         $this->assertNull($cache->get($key1), $type);

         $this->assertTrue($cache->set($key2, $value2), $type);
         $this->assertTrue($cache->set($key3, $value3), $type);
         $this->assertSame($value2, $cache->get($key2), $type);
         $this->assertSame($value3, $cache->get($key3), $type);
      }
   }

   public function testadd() {
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();
      foreach ($this->getAllBackends() as $type => $cache) {
         $this->assertTrue($cache->add($key1, $value1), $type);
         $this->assertFalse($cache->add($key1, $value1), $type);
         $this->assertSame($value1, $cache->get($key1), $type);
         $this->assertTrue($cache->add($key2, $value2), $type);
         $this->assertSame($value2, $cache->get($key2), $type);

         $this->assertTrue($cache->delete($key1), $type);
         $this->assertNull($cache->get($key1), $type);
         $this->assertTrue($cache->add($key1, $value1), $type);
         $this->assertSame($value1, $cache->get($key1), $type);
         $this->assertSame($value2, $cache->get($key2), $type);
      }
   }

   public function testincrement() {
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();
      foreach ($this->getAllBackends() as $type => $cache) {
         $this->assertNull($cache->get($key1), $type);
         $currentValue = 0;
         foreach ([1, 5, 100] as $amount) {
            $currentValue += $amount;
            $this->assertSame($currentValue, $cache->increment($key1, $amount),
             "$type | $amount");
         }

         $this->assertSame($currentValue, $cache->get($key1), $type);

         $this->assertTrue($cache->delete($key1));
         $this->assertSame(7, $cache->increment($key1, 7), $type);

         // TODO: Memcached has some strange behavior with these values that
         // doesn't appear to match the docs. It might have to do with
         // compression.
         if ($type !== 'Memcached') {
            $invalidValues = [
               'string',
               ['array'],
               (object)['object' => 'value']
            ];
            foreach ($invalidValues as $invalidValue) {
               $this->assertTrue($cache->set($key2, $invalidValue), $type);
               $this->assertSame(1, $cache->increment($key2), $type);
               $this->assertSame(1, $cache->get($key2), $type);
            }
         }
      }
   }

   public function testdecrement() {
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();
      foreach ($this->getAllBackends() as $type => $cache) {
         $this->assertNull($cache->get($key1), $type);

         // TODO: Memcached values cannot be decremented below 0 so we must
         // start it out higher.
         if ($type === 'Memcached') {
            $currentValue = 400;
            $cache->set($key1, $currentValue);
         } else {
            $currentValue = 0;
         }
         foreach ([1, 5, 100] as $amount) {
            $currentValue -= $amount;
            $this->assertSame($currentValue, $cache->decrement($key1, $amount),
             "$type | $amount");
         }

         $this->assertSame($currentValue, $cache->get($key1), $type);

         $this->assertTrue($cache->delete($key1));

         // TODO: Memcached has some strange behavior with these values that
         // doesn't appear to match the docs.
         if ($type !== 'Memcached') {
            $this->assertSame(-7, $cache->decrement($key1, 7), $type);

            $invalidValues = [
               'string',
               ['array'],
               (object)['object' => 'value']
            ];
            foreach ($invalidValues as $invalidValue) {
               $this->assertTrue($cache->set($key2, $invalidValue), $type);
               $this->assertSame(-1, $cache->decrement($key2), $type);
               $this->assertSame(-1, $cache->get($key2), $type);
            }
         }
      }
   }

   public function testgetAndSet() {
      foreach ($this->getAllBackends() as $type => $cache) {
         list($key, $value) = $this->getRandomKeyValue();

         $this->assertNull($cache->get($key), $type);

         $hit = false;
         $callback = function() use ($value, &$hit) {
            $hit = true;
            return $value;
         };

         $getAndSetValue = $cache->getAndSet($key, $callback);

         $this->assertTrue($hit, $type);
         $this->assertSame($value, $getAndSetValue, $type);
         $this->assertSame($value, $cache->get($key), $type);

         $hit = false;
         $getAndSetValue = $cache->getAndSet($key, $callback);

         $this->assertFalse($hit, $type);
         $this->assertSame($value, $getAndSetValue, $type);
         $this->assertSame($value, $cache->get($key), $type);
      }
   }

   private function getRandomKeyValue() {
      return [
         'key-' . rand(),
         'value-' . rand()
      ];
   }

   private function getMemcached() {
      $memcache = new Memcache();
      $memcache->pconnect('localhost', 11211);

      return $memcache;
   }

   private function getAllBackends() {
      return [
         'Enabled' => new Matryoshka\Enabled(new Matryoshka\MemoryArray()),
         'Hierarchy' => new Matryoshka\Hierarchy([
            new Matryoshka\MemoryArray(),
            new Matryoshka\MemoryArray()
         ]),
         'Memcached' => new Matryoshka\Memcached($this->getMemcached()),
         'Prefixed' => new Matryoshka\Prefixed(new Matryoshka\MemoryArray(), 'prefix'),
         'Scoped' => new Matryoshka\Scoped(new Matryoshka\MemoryArray(), 'scope')
      ];
   }
}
