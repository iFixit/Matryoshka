<?php

require_once __DIR__ . '/../library/iFixit/Matryoshka.php';

use iFixit\Matryoshka;

Matryoshka::autoload();

MatrysohkaBenchmark::run();

/**
 * Runs various benchmarks on various Backends.
 */
class MatrysohkaBenchmark {
   private static function benchmarkGetIncrementalKeys(
    Matryoshka\Backend $cache, $count) {
      for ($i = 0; $i < $count; $i++) {
         $cache->get("key-$i");
      }
   }

   private static function benchmarkSetIncrementalKeys(
    Matryoshka\Backend $cache, $count) {
      for ($i = 0; $i < $count; $i++) {
         $cache->set("key-$i", "value-$i");
      }
   }

   private static function benchmarkHitsSetup(Matryoshka\Backend $cache,
    $count) {
      $cache->set('testkey', 'testval');
   }
   private static function benchmarkHits(
    Matryoshka\Backend $cache, $count) {
      for ($i = 0; $i < $count; $i++) {
         $cache->get('testkey');
      }
   }

   public static function run() {
      $count = 1000;
      $allResults = [];
      $benchmarkMethods = self::getBenchmarkMethods();

      foreach ($benchmarkMethods as $method) {
         $benchmarkResults = [];
         $backends = self::getTestBackends();

         foreach ($backends as $type => $cache) {
            $setupMethodName = "{$method}Setup";
            if (method_exists('self', $setupMethodName)) {
               self::$setupMethodName($cache, $count);
            }
            $start = microtime(true);
            self::$method($cache, $count);
            $end = microtime(true);
            $time = $end - $start;
            $msPerCall = ($time / $count) * 1000;

            $benchmarkResults[$type] = [
               'time' => $time,
               'count' => $count,
               'msPerCall' => $msPerCall
            ];
         }

         $results[$method] = $benchmarkResults;
      }

      self::outputResults($results);
   }

   private static function outputResults($results) {
      foreach ($results as $benchmark => &$benchmarkResults) {
         uasort($benchmarkResults, function($result1, $result2) {
            // Make sure the difference is greater than 1.
            return ($result1['msPerCall'] - $result2['msPerCall']) * 10000;
         });
      } unset($benchmarkResults);

      uasort($results, function($benchmark1, $benchmark2) {
         // Reset returns the first element of the array.
         return (reset($benchmark1)['msPerCall'] -
          reset($benchmark2)['msPerCall']) * 10000;
      });

      echo json_encode($results, JSON_PRETTY_PRINT);
   }

   private static function getMemcache() {
      $memcache = new Memcache();
      $memcache->pconnect('localhost', 11211);

      return $memcache;
   }

   private static function getDisabled(Matryoshka\Backend $backend) {
      $disabled = new Matryoshka\Enabled($backend);
      $disabled->getsEnabled = false;
      $disabled->setsEnabled = false;
      $disabled->deletesEnabled = false;

      return $disabled;
   }

   private static function getTestBackends() {
      return [
         'EnabledMemoryArray' => new Matryoshka\Enabled(new Matryoshka\MemoryArray()),
         'DisabledMemoryArray' => self::getDisabled(new Matryoshka\MemoryArray()),
         'MemoryArrayHierarchy' => new Matryoshka\Hierarchy([
            new Matryoshka\MemoryArray()
         ]),
         'MemoryArrayMemcacheHierarchy' => new Matryoshka\Hierarchy([
            new Matryoshka\MemoryArray(),
            self::getMemcache()
         ]),
         'Memcache' => new Matryoshka\Memcache(self::getMemcache()),
         'MemoryArray' => new Matryoshka\MemoryArray(),
         'PrefixedMemoryArray' => new Matryoshka\Prefixed(new Matryoshka\MemoryArray(), 'prefix'),
         'ScopedMemoryArray' => new Matryoshka\Scoped(new Matryoshka\MemoryArray(), 'scope'),
         'StatsMemoryArray' => new Matryoshka\Stats(new Matryoshka\MemoryArray())
      ];
   }

   private static function getBenchmarkMethods() {
      $class = new ReflectionClass('MatrysohkaBenchmark');
      $methods = $class->getMethods();
      $benchmarkMethods = [];

      foreach ($methods as $method) {
         if (preg_match('/^benchmark/', $method->name) &&
             !preg_match('/Setup$/', $method->name)) {
            $benchmarkMethods[] = $method->name;
         }
      }

      return $benchmarkMethods;
   }
}
