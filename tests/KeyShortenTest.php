<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class KeyShortenTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\KeyShorten(new Matryoshka\Ephemeral(), 40);
   }

   public function testKeyShorten() {
      $maxLength = 50;
      $intactKeyLength = $maxLength - Matryoshka\KeyShorten::MD5_STRLEN;
      $memoryCache = new TestEphemeral();
      $cache = new Matryoshka\KeyShorten($memoryCache, $maxLength);

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
