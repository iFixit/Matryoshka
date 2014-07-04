<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class KeyShortenerTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\KeyShortener(new Matryoshka\MemoryArray(), 40);
   }

   public function testKeyShortener() {
      $maxLength = 50;
      $intactKeyLength = $maxLength - Matryoshka\KeyShortener::MD5_STRLEN;
      $memoryCache = new TestMemoryArray();
      $cache = new Matryoshka\KeyShortener($memoryCache, $maxLength);

      $keys = [
         'short',
         str_repeat('a', $maxLength),
         str_repeat('a', $maxLength + 1),
         str_repeat('a', $maxLength * 10)
      ];

      foreach ($keys as $key) {
         $cache->set($key, $key);
      }

      $cachedValues = $memoryCache->getCache();

      $this->assertCount(count($keys), $cachedValues);

      foreach ($cachedValues as $shortenedKey => $originalKey) {
         $this->assertLessThanOrEqual($maxLength, strlen($shortenedKey));

         $this->assertSame(substr($originalKey, 0, $intactKeyLength),
          substr($shortenedKey, 0, $intactKeyLength));
      }
   }
}
