<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class EphemeralTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Ephemeral();
   }
}
