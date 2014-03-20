<?php

require_once __DIR__ . '/../library/iFixit/Matryoshka.php';

use iFixit\Matryoshka;

Matryoshka::autoload();

MatrysohkaBenchmark::run();

/**
 * Runs various benchmarks on various Backends.
 */
class MatrysohkaBenchmark {
   // See displayHelp() for more info.
   private static $options = [
      'benchmark' => null,
      'backend' => null,
      'count' => 1000,
      'help' => null
   ];

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
   private static function benchmarkHits(Matryoshka\Backend $cache, $count) {
      for ($i = 0; $i < $count; $i++) {
         $cache->get('testkey');
      }
   }

   private static function benchmarkHalfHitsSetup(Matryoshka\Backend $cache,
    $count) {
      for ($i = 0; $i < $count; $i += 2) {
         $cache->set("halfHits{$i}");
      }
   }
   private static function benchmarkHalfHits(Matryoshka\Backend $cache,
    $count) {
      for ($i = 0; $i < $count; $i++) {
         $cache->get("halfHits{$i}");
      }
   }

   public static function run() {
      $options = self::getOptions();
      $count = $options['count'];
      $benchmarkRegex = $options['benchmark'];
      $backendRegex = $options['backend'];

      $allResults = [];
      $benchmarkMethods = self::getBenchmarkMethods($benchmarkRegex);

      foreach ($benchmarkMethods as $method) {
         $benchmarkResults = [];
         $backends = self::getTestBackends($backendRegex);

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

   private static function getOptions() {
      $availableOptions = [];
      foreach (self::$options as $option => $default) {
         $availableOptions[] = "{$option}::";
      }

      $options = getopt('', $availableOptions);

      if (array_key_exists('help', $options)) {
         self::displayHelp();
      }

      return array_merge(self::$options, $options);
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

   private static function getTestBackends($regex) {
      $allBackends = [
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

      if ($regex !== null) {
         foreach ($allBackends as $type => $backend) {
            if (!preg_match("/$regex/i", $type)) {
               unset($allBackends[$type]);
            }
         }
      }

      return $allBackends;
   }

   private static function getBenchmarkMethods($regex) {
      $class = new ReflectionClass('MatrysohkaBenchmark');
      $methods = $class->getMethods();
      $benchmarkMethods = [];

      foreach ($methods as $method) {
         if (preg_match('/^benchmark/', $method->name) &&
             !preg_match('/Setup$/', $method->name) && (
             $regex === null || preg_match("/$regex/i", $method->name))) {
            $benchmarkMethods[] = $method->name;
         }
      }

      return $benchmarkMethods;
   }

   private static function displayHelp() {
      echo <<<HELP
Runs benchmarks on backends to investigate performance overhead of different
backends.

   --backend=<regex> Filters backends based on the regex.

   --benchmark=<regex> Filters benchmarks based on the regex.

   --count=<count> Number of operations to perform/benchmark/backend.
HELP;
      exit(0);
   }
}
