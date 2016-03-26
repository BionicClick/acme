<?php

namespace Kelunik\Acme;

use Amp\Artax\Response;

class AcmeClientTest extends \PHPUnit_Framework_TestCase {
    protected function setUp() {
        \Amp\reactor(\Amp\driver());
        \Amp\Dns\resolver(\Amp\Dns\driver());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage directoryUri must be of type string
     */
    public function failsIfDirectoryUriNotString() {
        new AcmeClient(null, (new OpenSSLKeyGenerator)->generate());
    }

    /**
     * @test
     * @expectedException \Kelunik\Acme\AcmeException
     * @expectedExceptionMessage Could not obtain directory
     */
    public function failsIfDirectoryIsEmpty() {
        $client = new AcmeClient("http://127.0.0.1:4000/", (new OpenSSLKeyGenerator())->generate());
        \Amp\wait($client->get("foobar"));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage resource must be of type string
     */
    public function failsIfPostResourceIsEmpty() {
        $client = new AcmeClient("http://127.0.0.1:4000/directory", (new OpenSSLKeyGenerator())->generate());
        \Amp\wait($client->post(null, []));
    }

    /**
     * @test
     * @expectedException \Kelunik\Acme\AcmeException
     * @expectedExceptionMessage Resource not found in directory
     */
    public function failsIfResourceIsNoUriAndNotInDirectory() {
        $client = new AcmeClient("http://127.0.0.1:4000/directory", (new OpenSSLKeyGenerator())->generate());
        \Amp\wait($client->post("foobar", []));
    }

    /**
     * @test
     */
    public function canFetchDirectory() {
        $client = new AcmeClient("http://127.0.0.1:4000/directory", (new OpenSSLKeyGenerator())->generate());

        /** @var Response $response */
        $response = \Amp\wait($client->get("http://127.0.0.1:4000/directory"));
        $this->assertSame(200, $response->getStatus());

        $data = json_decode($response->getBody(), true);

        $this->assertInternalType("array", $data);
        $this->assertArrayHasKey("new-authz", $data);
        $this->assertArrayHasKey("new-cert", $data);
        $this->assertArrayHasKey("new-reg", $data);
        $this->assertArrayHasKey("revoke-cert", $data);
    }
}