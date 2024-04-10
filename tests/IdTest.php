<?php

use PHPUnit\Framework\TestCase;

include(__DIR__ . '/../lib/functions.php');

final class IdTest extends TestCase {

    public function testEntityIdParameter() {
        $entity_id = "https://dev-idp.renater.fr/idp/shibboleth";
        $sha1_id = sha1($entity_id);

        $this->assertEquals(
            $sha1_id,
            get_sha1_id(sprintf("%s", $entity_id)),
        );
    }

    public function testSha1Parameter() {
        $entity_id = "https://dev-idp.renater.fr/idp/shibboleth";
        $sha1_id = sha1($entity_id);

        $this->assertEquals(
            $sha1_id,
            get_sha1_id(sprintf("{sha1}%s", $sha1_id)),
        );
    }

}
